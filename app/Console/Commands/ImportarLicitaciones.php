<?php

namespace App\Console\Commands;

use App\Models\Adjudicacion;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Noki\XmlConverter\Convert;

class ImportarLicitaciones extends Command
{
    protected $signature = 'app:importar-licitaciones {--batch-size=500 : Tamaño del batch para inserciones}';
    protected $description = 'Importar licitaciones desde una fuente externa (optimizado)';

    // Cachés en memoria para evitar consultas repetidas
    private array $categoriasCache = [];
    private array $organismosCache = [];
    private array $empresasCache = [];

    public function handle()
    {
        $this->info("Iniciando la importación de licitaciones (versión optimizada)...");

        // Pre-cargar cachés
        $this->info("Cargando cachés en memoria...");
        $this->preloadCaches();

        $all = $this->ask("¿Deseas importar todas las licitaciones desde el año 2012? (s/n)", "s");

        if (strtolower($all) === 'n') {
            $year = $this->ask("¿Que año deseas importar?", 2012);
            $this->importarLicitacion($year);
        } else {
            for ($year = 2012; $year <= date('Y'); $year++) {
                $this->importarLicitacion($year);
            }
        }

        $this->info("\n✅ Importación completada.");
    }

    private function preloadCaches(): void
    {
        // Cargar todas las categorías en memoria
        $this->categoriasCache = Categoria::pluck('id', 'xml_id')->toArray();
        $this->info("  - Categorías cargadas: " . count($this->categoriasCache));

        // Cargar organismos existentes (identificador+nombre -> id)
        Organismo::select('id', 'identificador', 'nombre')->chunk(10000, function ($organismos) {
            foreach ($organismos as $org) {
                $key = $this->makeKey($org->identificador, $org->nombre);
                $this->organismosCache[$key] = $org->id;
            }
        });
        $this->info("  - Organismos cargados: " . count($this->organismosCache));

        // Cargar empresas existentes (identificador+nombre -> id)
        Empresa::select('id', 'identificador', 'nombre')->chunk(10000, function ($empresas) {
            foreach ($empresas as $emp) {
                $key = $this->makeKey($emp->identificador, $emp->nombre);
                $this->empresasCache[$key] = $emp->id;
            }
        });
        $this->info("  - Empresas cargadas: " . count($this->empresasCache));
    }

    private function makeKey(?string $identificador, ?string $nombre): string
    {
        return md5(($identificador ?? '') . '|' . ($nombre ?? ''));
    }

    function importarLicitacion($year)
    {
        $url = $this->retrieveUrl($year);
        $this->info("\nImportando licitaciones del año {$year}");
        $filename = "licitaciones_{$year}.zip";

        if (!Storage::disk('local')->exists('licitaciones')) {
            Storage::disk('local')->makeDirectory('licitaciones');
        }

        if (Storage::disk('local')->exists('licitaciones/' . $filename)) {
            $this->info("  → Archivo ya descargado");
        } else {
            $this->info("  → Descargando...");
            $file = file_get_contents($url);
            Storage::disk('local')->put('licitaciones/' . $filename, $file);
        }

        $filePath = Storage::disk('local')->path('licitaciones/' . $filename);
        $extractPath = Storage::disk('local')->path('licitaciones/extract_' . $year);

        if (Storage::disk('local')->exists('licitaciones/extract_' . $year)) {
            $this->info("  → Ya descomprimido");
        } else {
            Storage::disk('local')->makeDirectory('licitaciones/extract_' . $year);
            $zip = new \ZipArchive;
            if ($zip->open($filePath) === TRUE) {
                $this->info("  → Descomprimiendo...");
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                $this->error("No se pudo abrir el archivo zip.");
                return;
            }
        }

        $files = glob($extractPath . '/*.atom');
        $batchSize = (int) $this->option('batch-size');

        foreach ($files as $fileIndex => $file) {
            $this->info("  → Procesando archivo " . ($fileIndex + 1) . "/" . count($files));

            $xmlContent = file_get_contents($file);
            $json = Convert::xmlToJson($xmlContent);
            $data = json_decode($json, true);

            $feed = $data['feed'] ?? null;
            if (!$feed) continue;

            $entries = $feed['entry'] ?? [];
            if (empty($entries)) continue;

            // Procesar en batches
            $chunks = array_chunk($entries, $batchSize);
            $progressBar = $this->output->createProgressBar(count($entries));
            $progressBar->start();

            foreach ($chunks as $chunk) {
                $this->processBatch($chunk, $progressBar);
            }

            $progressBar->finish();
            $this->newLine();
        }

        $this->info("  ✓ Año {$year} completado");
    }

    private function processBatch(array $entries, $progressBar): void
    {
        $licitacionesData = [];
        $adjudicacionesData = [];
        $organismosToInsert = [];
        $empresasToInsert = [];

        foreach ($entries as $entry) {
            // Extraer datos del organismo
            $organismo = $entry['ContractFolderStatus']['LocatedContractingParty']['Party'] ?? null;
            $organismoNombre = $organismo['PartyName']['Name'] ?? null;
            $organismoIdentificador = $organismo['PartyIdentification']['ID']['value'] ?? null;
            $organismoKey = $this->makeKey($organismoIdentificador, $organismoNombre);

            // Si el organismo no existe en caché, preparar para inserción
            if (!isset($this->organismosCache[$organismoKey]) && $organismoNombre) {
                if (!isset($organismosToInsert[$organismoKey])) {
                    $organismosToInsert[$organismoKey] = [
                        'nombre' => $organismoNombre,
                        'identificador' => $organismoIdentificador,
                        'direccion' => $organismo['PostalAddress']['AddressLine']['Line'] ?? null,
                        'pais' => $organismo['PostalAddress']['Country']['Name'] ?? null,
                        'provincia' => $organismo['PostalAddress']['CityName'] ?? null,
                        'codigo_postal' => $organismo['PostalAddress']['PostalZone'] ?? null,
                        'contacto_nombre' => $organismo['Contact']['Name'] ?? null,
                        'contacto_telefono' => $organismo['Contact']['Telephone'] ?? null,
                        'contacto_fax' => $organismo['Contact']['Telefax'] ?? null,
                        'contacto_email' => $organismo['Contact']['ElectronicMail'] ?? null,
                        'sitio_web' => $organismo['WebsiteURI'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Extraer datos de empresa ganadora
            $empresaGanadora = $entry['ContractFolderStatus']['TenderResult']['WinningParty'] ?? null;
            $empresaNombre = $empresaGanadora['PartyName']['Name'] ?? null;
            $empresaIdentificador = $empresaGanadora['PartyIdentification']['ID']['value'] ?? null;
            $empresaKey = $this->makeKey($empresaIdentificador, $empresaNombre);

            if (!isset($this->empresasCache[$empresaKey]) && $empresaNombre) {
                if (!isset($empresasToInsert[$empresaKey])) {
                    $empresasToInsert[$empresaKey] = [
                        'nombre' => $empresaNombre,
                        'identificador' => $empresaIdentificador,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            $progressBar->advance();
        }

        // Insertar organismos nuevos en batch
        if (!empty($organismosToInsert)) {
            DB::table('organismos')->insertOrIgnore(array_values($organismosToInsert));
            // Recargar los nuevos IDs
            $newOrgs = Organismo::whereIn('identificador', array_column($organismosToInsert, 'identificador'))
                ->orWhereIn('nombre', array_column($organismosToInsert, 'nombre'))
                ->get(['id', 'identificador', 'nombre']);
            foreach ($newOrgs as $org) {
                $key = $this->makeKey($org->identificador, $org->nombre);
                $this->organismosCache[$key] = $org->id;
            }
        }

        // Insertar empresas nuevas en batch
        if (!empty($empresasToInsert)) {
            DB::table('empresas')->insertOrIgnore(array_values($empresasToInsert));
            // Recargar los nuevos IDs
            $newEmps = Empresa::whereIn('identificador', array_column($empresasToInsert, 'identificador'))
                ->orWhereIn('nombre', array_column($empresasToInsert, 'nombre'))
                ->get(['id', 'identificador', 'nombre']);
            foreach ($newEmps as $emp) {
                $key = $this->makeKey($emp->identificador, $emp->nombre);
                $this->empresasCache[$key] = $emp->id;
            }
        }

        // Ahora procesar licitaciones y adjudicaciones con los IDs en caché
        foreach ($entries as $entry) {
            $identificador = $entry['ContractFolderStatus']['ContractFolderID'] ?? null;
            if (!$identificador) continue;

            $organismo = $entry['ContractFolderStatus']['LocatedContractingParty']['Party'] ?? null;
            $organismoNombre = $organismo['PartyName']['Name'] ?? null;
            $organismoIdentificador = $organismo['PartyIdentification']['ID']['value'] ?? null;
            $organismoKey = $this->makeKey($organismoIdentificador, $organismoNombre);
            $organismoId = $this->organismosCache[$organismoKey] ?? null;

            $empresaGanadora = $entry['ContractFolderStatus']['TenderResult']['WinningParty'] ?? null;
            $empresaNombre = $empresaGanadora['PartyName']['Name'] ?? null;
            $empresaIdentificador = $empresaGanadora['PartyIdentification']['ID']['value'] ?? null;
            $empresaKey = $this->makeKey($empresaIdentificador, $empresaNombre);
            $empresaId = $this->empresasCache[$empresaKey] ?? null;

            $importes = $entry['ContractFolderStatus']['ProcurementProject']['BudgetAmount'] ?? null;
            $categoriaXmlId = $entry['ContractFolderStatus']['ProcurementProject']['RequiredCommodityClassification']['ItemClassificationCode']['value'] ?? null;

            $licitacionesData[] = [
                'identificador' => $identificador,
                'titulo' => $entry['title'] ?? null,
                'descripcion' => $entry['ContractFolderStatus']['TenderingTerms']['Description'] ?? null,
                'estado' => $entry['ContractFolderStatus']['ContractFolderStatusCode']['value'] ?? null,
                'importe_total' => $importes['TotalAmount']['value'] ?? null,
                'importe_final' => $importes['TaxExclusiveAmount']['value'] ?? null,
                'importe_estimado' => $importes['EstimatedOverallContractAmount']['value'] ?? null,
                'fecha_contratacion' => $this->parseDate($entry['ContractFolderStatus']['TenderResult']['Contract']['IssueDate'] ?? null),
                'fecha_actualizacion' => $this->parseDate($entry['updated'] ?? null),
                'categoria_id' => $this->categoriasCache[$categoriaXmlId] ?? null,
                'organismo_id' => $organismoId,
                'datos_raiz' => json_encode($entry),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Guardar datos para adjudicación después
            if ($empresaId) {
                $importeCobrado = $entry['ContractFolderStatus']['TenderResult']['AwardedTenderedProject']['LegalMonetaryTotal'] ?? null;
                $adjudicacionesData[$identificador] = [
                    'empresa_id' => $empresaId,
                    'importe' => $importeCobrado['PayableAmount']['value'] ?? null,
                    'importe_final' => $importeCobrado['TaxExclusiveAmount']['value'] ?? null,
                    'urgencia' => $entry['ContractFolderStatus']['TenderingProcess']['UrgencyCode']['value'] ?? null,
                    'tipo_procedimiento' => $entry['ContractFolderStatus']['TenderingProcess']['ProcedureCode']['value'] ?? null,
                    'descripcion' => $entry['ContractFolderStatus']['TenderResult']['Description'] ?? null,
                    'fecha_adjudicacion' => $this->parseDate($entry['ContractFolderStatus']['TenderResult']['Contract']['IssueDate'] ?? null),
                    'fecha_comienzo' => $this->parseDate($entry['ContractFolderStatus']['TenderResult']['StartDate'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Upsert licitaciones en batch
        if (!empty($licitacionesData)) {
            Licitacion::upsert(
                $licitacionesData,
                ['identificador'],
                ['titulo', 'descripcion', 'estado', 'importe_total', 'importe_final', 'importe_estimado', 
                 'fecha_contratacion', 'fecha_actualizacion', 'categoria_id', 'organismo_id', 'datos_raiz', 'updated_at']
            );
        }

        // Obtener IDs de licitaciones para adjudicaciones
        if (!empty($adjudicacionesData)) {
            $licitacionIds = Licitacion::whereIn('identificador', array_keys($adjudicacionesData))
                ->pluck('id', 'identificador')
                ->toArray();

            $adjudicacionesToInsert = [];
            foreach ($adjudicacionesData as $licitacionIdentificador => $adjData) {
                if (isset($licitacionIds[$licitacionIdentificador])) {
                    $adjData['licitacion_id'] = $licitacionIds[$licitacionIdentificador];
                    $adjudicacionesToInsert[] = $adjData;
                }
            }

            if (!empty($adjudicacionesToInsert)) {
                Adjudicacion::upsert(
                    $adjudicacionesToInsert,
                    ['licitacion_id', 'empresa_id'],
                    ['importe', 'importe_final', 'urgencia', 'tipo_procedimiento', 'descripcion', 
                     'fecha_adjudicacion', 'fecha_comienzo', 'updated_at']
                );
            }
        }
    }

    function retrieveUrl($year)
    {
        return "https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643/licitacionesPerfilesContratanteCompleto3_" . $year . ".zip";
    }

    private function parseDate($date)
    {
        if (!$date) return null;
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
