<?php

namespace Modules\Presupuestos\Services;

class GobiertoParser
{
    /**
     * Parsea JSON de la API de Gobierto.
     * URL: https://presupuestos.gobierto.es/api/data/{INE}/{YEAR}/{G|I}/{functional|economic}.json
     */
    public function parseJson(array $data, string $tipoPresupuesto, string $tipoClasificacion): array
    {
        $partidas = [];

        foreach ($data as $item) {
            $parsed = $this->mapItem($item, $tipoPresupuesto, $tipoClasificacion);
            if ($parsed) {
                $partidas[] = $parsed;
            }
        }

        return $partidas;
    }

    protected function mapItem(array $item, string $tipoPresupuesto, string $tipoClasificacion): ?array
    {
        $codigo = $item['code'] ?? $item['id'] ?? null;
        $importe = $item['budget'] ?? $item['amount'] ?? $item['value'] ?? null;
        $nombre = $item['name'] ?? $item['label'] ?? null;

        if ($codigo === null || $importe === null) return null;

        $result = [
            'tipo_presupuesto' => $tipoPresupuesto,
            'credito_inicial' => (float) $importe,
            'fuente' => 'gobierto',
            '_clasificacion_nombre' => $nombre,
        ];

        if ($tipoClasificacion === 'functional') {
            $result['codigo_funcional'] = (string) $codigo;
        } else {
            $result['codigo_economica'] = (string) $codigo;
        }

        // Nivel basado en longitud del código
        $result['_nivel'] = strlen((string) $codigo);

        return $result;
    }

    /**
     * Extrae clasificaciones únicas del dataset para upsert.
     */
    public function extractClasificaciones(array $data, string $tipoClasificacion): array
    {
        $clasificaciones = [];

        foreach ($data as $item) {
            $codigo = (string) ($item['code'] ?? $item['id'] ?? '');
            $nombre = $item['name'] ?? $item['label'] ?? '';

            if (!$codigo || !$nombre) continue;

            $nivel = strlen($codigo);
            $codigoPadre = $nivel > 1 ? substr($codigo, 0, $nivel - 1) : null;
            $tipo = $tipoClasificacion === 'functional' ? 'funcional' : 'economica';

            $clasificaciones[$tipo . ':' . $codigo] = [
                'tipo' => $tipo,
                'codigo' => $codigo,
                'codigo_padre' => $codigoPadre,
                'nivel' => $nivel,
                'nombre' => $nombre,
            ];
        }

        return array_values($clasificaciones);
    }
}
