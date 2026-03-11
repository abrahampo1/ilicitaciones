<?php

namespace Modules\Contratos\Jobs;

use Modules\Contratos\Models\Adjudicacion;
use Modules\Contratos\Models\Categoria;
use Modules\Contratos\Models\Empresa;
use Modules\Contratos\Models\Licitacion;
use Modules\Contratos\Models\Organismo;
use Modules\Contratos\Services\PlacspParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPlacspFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    // In-memory caches
    private array $organismosCache = [];
    private array $empresasCache = [];
    private array $categoriasCache = [];

    // Redis cache key prefix and TTL (2 hours)
    private const CACHE_TAG = 'placsp_import';
    private const CACHE_TTL = 7200;

    public function __construct(
        public string $filePath,
    ) {}

    public function handle(PlacspParser $parser): void
    {
        $content = file_get_contents($this->filePath);
        if (!$content) {
            Log::error("PLACSP: No se pudo leer {$this->filePath}");
            return;
        }

        // Pre-load caches (from Redis if available, otherwise from DB)
        $this->preloadCaches();

        $contracts = $parser->parseAtomFile($content);
        $upserted = 0;

        // Process in batches of 500
        $chunks = array_chunk($contracts, 500);

        foreach ($chunks as $chunk) {
            $this->processBatch($chunk);
            $upserted += count($chunk);
        }

        Log::info("PLACSP: Procesado {$this->filePath} — {$upserted} contratos procesados");
    }

    private function preloadCaches(): void
    {
        // Categorias are small, always load from DB
        $this->categoriasCache = Categoria::pluck('id', 'xml_id')->toArray();

        // Try Redis first for organismos
        $cachedOrganismos = Cache::tags([self::CACHE_TAG])->get('organismos_cache');
        if ($cachedOrganismos !== null) {
            $this->organismosCache = $cachedOrganismos;
        } else {
            Organismo::select('id', 'identificador', 'nombre', 'dir3_code')->chunk(10000, function ($organismos) {
                foreach ($organismos as $org) {
                    if ($org->dir3_code) {
                        $this->organismosCache['dir3:' . $org->dir3_code] = $org->id;
                    }
                    $key = $this->makeOrganismoKey($org->identificador, $org->nombre);
                    $this->organismosCache[$key] = $org->id;
                }
            });

            Cache::tags([self::CACHE_TAG])->put('organismos_cache', $this->organismosCache, self::CACHE_TTL);
        }

        // Try Redis first for empresas
        $cachedEmpresas = Cache::tags([self::CACHE_TAG])->get('empresas_cache');
        if ($cachedEmpresas !== null) {
            $this->empresasCache = $cachedEmpresas;
        } else {
            Empresa::select('id', 'identificador', 'nombre', 'nif')->chunk(10000, function ($empresas) {
                foreach ($empresas as $emp) {
                    if ($emp->nif) {
                        $this->empresasCache['nif:' . $emp->nif] = $emp->id;
                    }
                    $key = $this->makeEmpresaKey($emp->identificador, $emp->nombre);
                    $this->empresasCache[$key] = $emp->id;
                }
            });

            Cache::tags([self::CACHE_TAG])->put('empresas_cache', $this->empresasCache, self::CACHE_TTL);
        }
    }

    private function processBatch(array $entries): void
    {
        DB::transaction(function () use ($entries) {
            // ── Pass 1: Bulk-create unknown organismos and empresas ──

            $newOrganismos = [];
            $newEmpresas = [];

            foreach ($entries as $data) {
                if (empty($data['external_id']) || empty($data['expediente'])) {
                    continue;
                }

                // Collect unknown organismos
                $orgNombre = $data['organo_contratante'] ?? null;
                $orgDir3 = $data['organo_dir3'] ?? null;

                if ($orgNombre || $orgDir3) {
                    $inCache = false;
                    if ($orgDir3 && isset($this->organismosCache['dir3:' . $orgDir3])) {
                        $inCache = true;
                    }
                    if (!$inCache) {
                        $key = $this->makeOrganismoKey($orgDir3, $orgNombre);
                        if (isset($this->organismosCache[$key])) {
                            $inCache = true;
                        }
                    }

                    if (!$inCache) {
                        $key = $this->makeOrganismoKey($orgDir3, $orgNombre);
                        // Deduplicate within the batch by key
                        if (!isset($newOrganismos[$key])) {
                            $newOrganismos[$key] = array_merge(
                                [
                                    'nombre' => $orgNombre,
                                    'identificador' => $orgDir3,
                                    'dir3_code' => $orgDir3,
                                    'organismo_superior' => $data['organo_superior'] ?? null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ],
                                $data['_organismo_data'] ?? []
                            );
                        }
                    }
                }

                // Collect unknown empresas
                $empNombre = $data['adjudicatario_nombre'] ?? null;
                $empNif = $data['adjudicatario_nif'] ?? null;

                if ($empNombre || $empNif) {
                    $inCache = false;
                    if ($empNif && isset($this->empresasCache['nif:' . $empNif])) {
                        $inCache = true;
                    }
                    if (!$inCache) {
                        $key = $this->makeEmpresaKey($empNif, $empNombre);
                        if (isset($this->empresasCache[$key])) {
                            $inCache = true;
                        }
                    }

                    if (!$inCache) {
                        $key = $this->makeEmpresaKey($empNif, $empNombre);
                        if (!isset($newEmpresas[$key])) {
                            $newEmpresas[$key] = [
                                'nombre' => $empNombre,
                                'identificador' => $empNif,
                                'nif' => $empNif,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            }

            // Bulk insert new organismos (insertOrIgnore handles race conditions)
            if (!empty($newOrganismos)) {
                // Normalize all rows to have the same keys (union of all keys)
                $allKeys = [];
                foreach ($newOrganismos as $org) {
                    $allKeys = array_merge($allKeys, array_keys($org));
                }
                $allKeys = array_unique($allKeys);
                $defaults = array_fill_keys($allKeys, null);
                $normalized = array_map(fn($org) => array_merge($defaults, $org), array_values($newOrganismos));

                Organismo::insertOrIgnore($normalized);

                // Refresh cache: query back the newly inserted organismos by dir3/nombre
                $dir3Codes = array_filter(array_column(array_values($newOrganismos), 'dir3_code'));
                $nombres = array_filter(array_column(array_values($newOrganismos), 'nombre'));

                $query = Organismo::select('id', 'identificador', 'nombre', 'dir3_code');
                if (!empty($dir3Codes) && !empty($nombres)) {
                    $query->where(function ($q) use ($dir3Codes, $nombres) {
                        $q->whereIn('dir3_code', $dir3Codes)
                          ->orWhereIn('nombre', $nombres);
                    });
                } elseif (!empty($dir3Codes)) {
                    $query->whereIn('dir3_code', $dir3Codes);
                } else {
                    $query->whereIn('nombre', $nombres);
                }

                foreach ($query->get() as $org) {
                    if ($org->dir3_code) {
                        $this->organismosCache['dir3:' . $org->dir3_code] = $org->id;
                    }
                    $key = $this->makeOrganismoKey($org->identificador, $org->nombre);
                    $this->organismosCache[$key] = $org->id;
                }

                // Update Redis cache with new entries
                Cache::tags([self::CACHE_TAG])->put('organismos_cache', $this->organismosCache, self::CACHE_TTL);
            }

            // Bulk insert new empresas
            if (!empty($newEmpresas)) {
                Empresa::insertOrIgnore(array_values($newEmpresas));

                // Refresh cache
                $nifs = array_filter(array_column(array_values($newEmpresas), 'nif'));
                $nombres = array_filter(array_column(array_values($newEmpresas), 'nombre'));

                $query = Empresa::select('id', 'identificador', 'nombre', 'nif');
                if (!empty($nifs) && !empty($nombres)) {
                    $query->where(function ($q) use ($nifs, $nombres) {
                        $q->whereIn('nif', $nifs)
                          ->orWhereIn('nombre', $nombres);
                    });
                } elseif (!empty($nifs)) {
                    $query->whereIn('nif', $nifs);
                } else {
                    $query->whereIn('nombre', $nombres);
                }

                foreach ($query->get() as $emp) {
                    if ($emp->nif) {
                        $this->empresasCache['nif:' . $emp->nif] = $emp->id;
                    }
                    $key = $this->makeEmpresaKey($emp->identificador, $emp->nombre);
                    $this->empresasCache[$key] = $emp->id;
                }

                // Update Redis cache with new entries
                Cache::tags([self::CACHE_TAG])->put('empresas_cache', $this->empresasCache, self::CACHE_TTL);
            }

            // ── Pass 2: Build licitaciones/adjudicaciones data with all IDs resolved ──

            $licitacionesData = [];
            $adjudicacionesData = [];
            foreach ($entries as $data) {
                if (empty($data['external_id']) || empty($data['expediente'])) {
                    continue;
                }

                // Resolve Organismo (all IDs should be in cache now)
                $organismoId = $this->resolveOrganismoFromCache($data);

                // Resolve Empresa (all IDs should be in cache now)
                $empresaId = $this->resolveEmpresaFromCache($data);

                // Resolve category from CPV codes
                $categoriaId = null;
                if (!empty($data['cpv_codes']) && is_array($data['cpv_codes'])) {
                    foreach ($data['cpv_codes'] as $cpv) {
                        if (isset($this->categoriasCache[$cpv])) {
                            $categoriaId = $this->categoriasCache[$cpv];
                            break;
                        }
                    }
                }

                $licitacionesData[] = [
                    'identificador' => $data['expediente'],
                    'titulo' => $data['objeto'] ?? null,
                    'url' => $data['link'] ?? null,
                    'id_url' => $data['external_id'],
                    'estado' => isset($data['status_code']) ? (Licitacion::STATUS_LABELS[$data['status_code']] ?? $data['status_code']) : null,
                    'importe_total' => $data['importe_con_iva'] ?? null,
                    'importe_final' => $data['importe_sin_iva'] ?? null,
                    'importe_estimado' => $data['valor_estimado'] ?? null,
                    'fecha_contratacion' => $data['fecha_formalizacion'] ?? null,
                    'fecha_actualizacion' => $data['updated_at_source'] ?? now(),
                    'categoria_id' => $categoriaId,
                    'organismo_id' => $organismoId,
                    // Enriched fields
                    'expediente' => $data['expediente'],
                    'status_code' => $data['status_code'] ?? null,
                    'tipo_contrato_code' => $data['tipo_contrato_code'] ?? null,
                    'subtipo_contrato_code' => $data['subtipo_contrato_code'] ?? null,
                    'procedimiento_code' => $data['procedimiento_code'] ?? null,
                    'urgencia_code' => $data['urgencia_code'] ?? null,
                    'importe_sin_iva' => $data['importe_sin_iva'] ?? null,
                    'importe_con_iva' => $data['importe_con_iva'] ?? null,
                    'valor_estimado' => $data['valor_estimado'] ?? null,
                    'cpv_codes' => isset($data['cpv_codes']) ? json_encode($data['cpv_codes']) : null,
                    'comunidad_autonoma' => $data['comunidad_autonoma'] ?? null,
                    'nuts_code' => $data['nuts_code'] ?? null,
                    'lugar_ejecucion' => $data['lugar_ejecucion'] ?? null,
                    'duracion' => $data['duracion'] ?? null,
                    'duracion_unidad' => $data['duracion_unidad'] ?? null,
                    'adjudicatario_nombre' => $data['adjudicatario_nombre'] ?? null,
                    'adjudicatario_nif' => $data['adjudicatario_nif'] ?? null,
                    'importe_adjudicacion_sin_iva' => $data['importe_adjudicacion_sin_iva'] ?? null,
                    'importe_adjudicacion_con_iva' => $data['importe_adjudicacion_con_iva'] ?? null,
                    'fecha_presentacion_limite' => $data['fecha_presentacion_limite'] ?? null,
                    'fecha_inicio' => $data['fecha_inicio'] ?? null,
                    'fecha_fin' => $data['fecha_fin'] ?? null,
                    'fecha_adjudicacion' => $data['fecha_adjudicacion'] ?? null,
                    'fecha_formalizacion' => $data['fecha_formalizacion'] ?? null,
                    'resultado_code' => $data['resultado_code'] ?? null,
                    'num_ofertas' => $data['num_ofertas'] ?? null,
                    'external_id' => $data['external_id'],
                    'link' => $data['link'] ?? null,
                    'synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Prepare adjudicacion if there's a winner
                if ($empresaId && !empty($data['adjudicatario_nombre'])) {
                    $adjudicacionesData[$data['expediente']] = [
                        'empresa_id' => $empresaId,
                        'importe' => $data['importe_adjudicacion_con_iva'] ?? null,
                        'importe_final' => $data['importe_adjudicacion_sin_iva'] ?? null,
                        'urgencia' => $data['urgencia_code'] ?? null,
                        'tipo_procedimiento' => $data['procedimiento_code'] ?? null,
                        'fecha_adjudicacion' => $data['fecha_adjudicacion'] ?? null,
                        'fecha_comienzo' => $data['fecha_inicio'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Batch upsert licitaciones (deduplicate by external_id, keep last)
            if (!empty($licitacionesData)) {
                $licitacionesData = array_values(
                    collect($licitacionesData)->keyBy('external_id')->all()
                );
                Licitacion::upsert(
                    $licitacionesData,
                    ['external_id'],
                    [
                        'titulo', 'estado', 'importe_total', 'importe_final', 'importe_estimado',
                        'fecha_contratacion', 'fecha_actualizacion', 'categoria_id', 'organismo_id',
                        'expediente', 'status_code', 'tipo_contrato_code', 'subtipo_contrato_code',
                        'procedimiento_code', 'urgencia_code',
                        'importe_sin_iva', 'importe_con_iva', 'valor_estimado',
                        'cpv_codes', 'comunidad_autonoma', 'nuts_code', 'lugar_ejecucion',
                        'duracion', 'duracion_unidad',
                        'adjudicatario_nombre', 'adjudicatario_nif',
                        'importe_adjudicacion_sin_iva', 'importe_adjudicacion_con_iva',
                        'fecha_presentacion_limite', 'fecha_inicio', 'fecha_fin',
                        'fecha_adjudicacion', 'fecha_formalizacion',
                        'resultado_code', 'num_ofertas', 'link', 'synced_at', 'updated_at',
                    ]
                );
            }

            // Upsert adjudicaciones
            if (!empty($adjudicacionesData)) {
                $licitacionIds = Licitacion::whereIn('identificador', array_keys($adjudicacionesData))
                    ->pluck('id', 'identificador')
                    ->toArray();

                $adjToInsert = [];
                foreach ($adjudicacionesData as $identificador => $adjData) {
                    if (isset($licitacionIds[$identificador])) {
                        $adjData['licitacion_id'] = $licitacionIds[$identificador];
                        $adjToInsert[] = $adjData;
                    }
                }

                if (!empty($adjToInsert)) {
                    Adjudicacion::upsert(
                        $adjToInsert,
                        ['licitacion_id', 'empresa_id'],
                        ['importe', 'importe_final', 'urgencia', 'tipo_procedimiento', 'fecha_adjudicacion', 'fecha_comienzo', 'updated_at']
                    );
                }
            }
        });
    }

    /**
     * Resolve organismo ID from cache only (Pass 2 — all entities already bulk-inserted).
     */
    private function resolveOrganismoFromCache(array $data): ?int
    {
        $nombre = $data['organo_contratante'] ?? null;
        $dir3 = $data['organo_dir3'] ?? null;

        if (!$nombre && !$dir3) return null;

        if ($dir3 && isset($this->organismosCache['dir3:' . $dir3])) {
            return $this->organismosCache['dir3:' . $dir3];
        }

        $key = $this->makeOrganismoKey($dir3, $nombre);
        return $this->organismosCache[$key] ?? null;
    }

    /**
     * Resolve empresa ID from cache only (Pass 2 — all entities already bulk-inserted).
     */
    private function resolveEmpresaFromCache(array $data): ?int
    {
        $nombre = $data['adjudicatario_nombre'] ?? null;
        $nif = $data['adjudicatario_nif'] ?? null;

        if (!$nombre && !$nif) return null;

        if ($nif && isset($this->empresasCache['nif:' . $nif])) {
            return $this->empresasCache['nif:' . $nif];
        }

        $key = $this->makeEmpresaKey($nif, $nombre);
        return $this->empresasCache[$key] ?? null;
    }

    private function makeOrganismoKey(?string $identificador, ?string $nombre): string
    {
        if (empty($identificador) && empty($nombre)) {
            return Str::uuid()->toString();
        }
        return md5(($identificador ?? '') . '|' . ($nombre ?? ''));
    }

    private function makeEmpresaKey(?string $identificador, ?string $nombre = null): string
    {
        if (!empty($identificador)) {
            return md5($identificador);
        }
        if (!empty($nombre)) {
            return md5('nombre:' . $nombre);
        }
        return Str::uuid()->toString();
    }
}
