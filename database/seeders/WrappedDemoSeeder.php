<?php

namespace Database\Seeders;

use App\Jobs\RecalcularEstadisticas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos sintéticos multi-año para probar el Wrapped en local (no usar en producción).
 * Genera organismos, empresas, categorías y varios miles de adjudicaciones repartidas
 * entre 2021 y el año actual, con estacionalidad, urgencias y procedimientos CODICE.
 *
 *   php artisan db:seed --class=WrappedDemoSeeder
 */
class WrappedDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('WrappedDemoSeeder es solo para local/testing.');

            return;
        }

        mt_srand(2026); // reproducible

        $now = now();

        $categorias = $this->crearCategorias($now);
        $organismos = $this->crearOrganismos($now);
        $empresas = $this->crearEmpresas($now);

        $anioActual = (int) $now->format('Y');
        $licitacionId = (int) DB::table('licitacions')->max('id');

        foreach (range(2021, $anioActual) as $year) {
            $licitaciones = [];
            $adjudicaciones = [];
            $numLicitaciones = mt_rand(450, 750);

            for ($i = 0; $i < $numLicitaciones; $i++) {
                $licitacionId++;

                // Estacionalidad: más peso en junio y sobre todo en diciembre.
                $mes = $this->mesConEstacionalidad();
                $dia = mt_rand(1, 28);
                $fecha = sprintf('%d-%02d-%02d', $year, $mes, $dia);

                // Distribución muy sesgada: muchos contratos pequeños, pocos enormes.
                $importe = round(exp(mt_rand(950, 1750) / 100), 2); // ~13k € a ~40M €
                if (mt_rand(1, 400) === 1) {
                    $importe = round($importe * mt_rand(8, 20), 2); // el "hit del año"
                }

                $organismo = $organismos[mt_rand(0, count($organismos) - 1)];

                $licitaciones[] = [
                    'id' => $licitacionId,
                    'titulo' => $this->tituloLicitacion(),
                    'descripcion' => null,
                    'identificador' => "DEMO-{$year}-".str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                    'estado' => 'RES',
                    'importe_total' => $importe,
                    'importe_final' => $importe,
                    'fecha_contratacion' => $fecha,
                    'fecha_actualizacion' => $fecha.' 12:00:00',
                    'organismo_id' => $organismo,
                    'categoria_id' => $categorias[mt_rand(0, count($categorias) - 1)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $adjudicaciones[] = [
                    'licitacion_id' => $licitacionId,
                    'empresa_id' => $empresas[mt_rand(0, count($empresas) - 1)],
                    'importe' => $importe,
                    'importe_final' => $importe,
                    'urgencia' => $this->elegir(['1' => 85, '2' => 12, '3' => 3]),
                    'tipo_procedimiento' => $this->elegir(['1' => 62, '2' => 10, '3' => 13, '6' => 15]),
                    'fecha_adjudicacion' => $fecha,
                    'fecha_comienzo' => $fecha,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($licitaciones, 200) as $chunk) {
                DB::table('licitacions')->insert($chunk);
            }
            foreach (array_chunk($adjudicaciones, 200) as $chunk) {
                DB::table('adjudicacions')->insert($chunk);
            }

            $this->command?->info("Año {$year}: {$numLicitaciones} licitaciones + adjudicaciones.");
        }

        // Deja el resto del sitio (home, fichas) coherente con los nuevos datos.
        (new RecalcularEstadisticas)->handle();

        $this->command?->info('Agregados recalculados. Demo lista.');
    }

    private function crearCategorias($now): array
    {
        $nombres = [
            'Servicios de construcción', 'Servicios sanitarios y sociales', 'Servicios de transporte',
            'Equipamiento informático', 'Servicios de limpieza', 'Suministro de energía',
            'Obras de carreteras', 'Servicios educativos', 'Mobiliario urbano', 'Servicios jurídicos',
            'Telecomunicaciones', 'Alimentación y catering', 'Seguridad y vigilancia',
            'Consultoría y auditoría', 'Mantenimiento de edificios',
        ];

        $ids = [];
        foreach ($nombres as $i => $nombre) {
            $ids[] = DB::table('categorias')->insertGetId([
                'nombre' => $nombre,
                'xml_id' => 90000000 + $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    private function crearOrganismos($now): array
    {
        $lugares = [
            'Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza', 'Málaga', 'Murcia', 'Bilbao',
            'Alicante', 'Córdoba', 'Valladolid', 'Vigo', 'Gijón', 'La Coruña', 'Granada', 'Oviedo',
            'Santander', 'Pamplona', 'Toledo', 'Badajoz',
        ];
        $tipos = ['Ayuntamiento de %s', 'Diputación Provincial de %s', 'Universidad de %s', 'Servicio de Salud de %s'];
        $ministerios = [
            'Ministerio de Transportes y Movilidad Sostenible', 'Ministerio de Sanidad',
            'Ministerio de Defensa', 'Ministerio del Interior', 'Ministerio para la Transición Ecológica',
            'Agencia Estatal de Administración Tributaria', 'ADIF - Administrador de Infraestructuras Ferroviarias',
            'Renfe Operadora', 'Correos y Telégrafos S.A.', 'Instituto Nacional de la Seguridad Social',
        ];

        $ids = [];
        foreach ($ministerios as $i => $nombre) {
            $ids[] = DB::table('organismos')->insertGetId([
                'nombre' => $nombre,
                'identificador' => 'DEMO-MIN-'.$i,
                'provincia' => 'Madrid',
                'pais' => 'España',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        foreach ($lugares as $i => $lugar) {
            foreach ($tipos as $j => $tipo) {
                if (mt_rand(0, 2) === 0) {
                    continue; // no todos los cruces
                }
                $ids[] = DB::table('organismos')->insertGetId([
                    'nombre' => sprintf($tipo, $lugar),
                    'identificador' => "DEMO-ORG-{$i}-{$j}",
                    'provincia' => $lugar,
                    'pais' => 'España',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $ids;
    }

    private function crearEmpresas($now): array
    {
        $bases = [
            'Construcciones Iberia', 'Servicios Meridional', 'Tecnologías Atlántico', 'Grupo Levante',
            'Infraestructuras del Norte', 'Limpiezas Castellana', 'Energía Peninsular', 'Logística Cantábrica',
            'Consultoría Mediterránea', 'Seguridad Íbera', 'Obras y Reformas Duero', 'Catering Meseta',
            'Telecomunicaciones Estrella', 'Mantenimientos Guadiana', 'Ingeniería Pirineo',
        ];
        $sufijos = ['S.L.', 'S.A.', 'S.A.U.', 'S. Coop.', 'y Asociados S.L.'];

        $ids = [];
        $n = 0;
        foreach ($bases as $base) {
            foreach ($sufijos as $sufijo) {
                foreach (['', ' 2000', ' Global', ' Servicios', ' XXI', ' Facility', ' Green', ' Plus'] as $extra) {
                    if (mt_rand(0, 2) > 0) {
                        continue;
                    }
                    $ids[] = DB::table('empresas')->insertGetId([
                        'nombre' => $base.$extra.' '.$sufijo,
                        'identificador' => 'DEMO-B'.str_pad((string) $n++, 7, '0', STR_PAD_LEFT),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        return $ids;
    }

    private function tituloLicitacion(): string
    {
        $acciones = [
            'Servicio de mantenimiento de', 'Obras de rehabilitación de', 'Suministro de equipamiento para',
            'Contrato de limpieza de', 'Redacción del proyecto de', 'Gestión integral de',
            'Servicio de vigilancia de', 'Ampliación de', 'Modernización de', 'Explotación de',
        ];
        $objetos = [
            'edificios municipales', 'la red de carreteras provinciales', 'centros de salud',
            'instalaciones deportivas', 'el alumbrado público', 'colegios públicos', 'la estación intermodal',
            'parques y jardines', 'la red de abastecimiento de agua', 'las dependencias administrativas',
            'el hospital comarcal', 'sistemas informáticos corporativos',
        ];

        return $acciones[mt_rand(0, count($acciones) - 1)].' '.$objetos[mt_rand(0, count($objetos) - 1)];
    }

    private function mesConEstacionalidad(): int
    {
        // Pesos por mes: diciembre dispara, agosto se hunde.
        $pesos = [1 => 7, 8, 9, 8, 9, 12, 9, 4, 8, 9, 10, 18];
        $total = array_sum($pesos);
        $r = mt_rand(1, $total);
        foreach ($pesos as $mes => $peso) {
            $r -= $peso;
            if ($r <= 0) {
                return $mes;
            }
        }

        return 12;
    }

    /**
     * Elección ponderada: ['valor' => peso, ...].
     */
    private function elegir(array $pesos): string
    {
        $r = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $valor => $peso) {
            $r -= $peso;
            if ($r <= 0) {
                return (string) $valor;
            }
        }

        return (string) array_key_first($pesos);
    }
}
