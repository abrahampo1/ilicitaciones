<?php

namespace Modules\Presupuestos\Services;

class PgeParser
{
    /**
     * Parsea un CSV del PGE (semicolon-separated, ISO-8859-1).
     * Retorna array estructurado para upsert.
     */
    public function parseCsv(string $filePath, string $tipoPresupuesto = 'gastos'): array
    {
        $content = file_get_contents($filePath);
        $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');

        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines), ';');
        $headers = array_map('trim', $headers);

        $partidas = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $row = str_getcsv($line, ';');
            if (count($row) < count($headers)) continue;

            $data = array_combine($headers, $row);
            $parsed = $this->mapRow($data, $tipoPresupuesto);
            if ($parsed) {
                $partidas[] = $parsed;
            }
        }

        return $partidas;
    }

    /**
     * Mapea una fila CSV a nuestro schema.
     *
     * Columnas típicas del PGE:
     * - Sección / Servicio (orgánica)
     * - Programa (funcional)
     * - Capítulo / Artículo / Concepto / Subconcepto (económica)
     * - Importe (crédito inicial)
     */
    protected function mapRow(array $data, string $tipoPresupuesto): ?array
    {
        // Intentar detectar las columnas — el PGE varía el formato entre años
        $seccion = $this->findColumn($data, ['Sección', 'Seccion', 'SECCION', 'SEC']);
        $servicio = $this->findColumn($data, ['Servicio', 'SERVICIO', 'SER']);
        $programa = $this->findColumn($data, ['Programa', 'PROGRAMA', 'PRO']);
        $capitulo = $this->findColumn($data, ['Capítulo', 'Capitulo', 'CAPITULO', 'CAP']);
        $articulo = $this->findColumn($data, ['Artículo', 'Articulo', 'ARTICULO', 'ART']);
        $concepto = $this->findColumn($data, ['Concepto', 'CONCEPTO', 'CON']);
        $subconcepto = $this->findColumn($data, ['Subconcepto', 'SUBCONCEPTO', 'SUB']);

        $importe = $this->findColumn($data, ['Importe', 'IMPORTE', 'Créditos', 'Creditos', 'CREDITOS', 'Miles de euros', 'Euros']);

        if ($importe === null) return null;

        // Limpiar importe (puede tener separadores de miles con punto y decimal con coma)
        $importe = str_replace(['.', ' '], '', $importe);
        $importe = str_replace(',', '.', $importe);
        $importe = (float) $importe;

        // Construir códigos
        $codigoOrganica = trim(($seccion ?? '') . ($servicio ? '.' . $servicio : ''));
        $codigoFuncional = trim($programa ?? '');
        $codigoEconomica = trim(($capitulo ?? '') . ($articulo ?? '') . ($concepto ?? '') . ($subconcepto ?? ''));

        if (!$codigoEconomica && !$codigoFuncional) return null;

        return [
            'tipo_presupuesto' => $tipoPresupuesto,
            'codigo_organica' => $codigoOrganica ?: null,
            'codigo_funcional' => $codigoFuncional ?: null,
            'codigo_economica' => $codigoEconomica ?: null,
            'credito_inicial' => $importe,
            'fuente' => 'pge',
        ];
    }

    protected function findColumn(array $data, array $possibleNames): ?string
    {
        foreach ($possibleNames as $name) {
            if (isset($data[$name]) && trim($data[$name]) !== '') {
                return trim($data[$name]);
            }
        }
        return null;
    }

    /**
     * Parsea XML del PGE (formato datos.gob.es).
     */
    public function parseXml(string $filePath, string $tipoPresupuesto = 'gastos'): array
    {
        $content = file_get_contents($filePath);
        $xml = new \SimpleXMLElement($content);

        $partidas = [];
        foreach ($xml->children() as $entry) {
            $parsed = $this->mapXmlEntry($entry, $tipoPresupuesto);
            if ($parsed) {
                $partidas[] = $parsed;
            }
        }

        return $partidas;
    }

    protected function mapXmlEntry(\SimpleXMLElement $entry, string $tipoPresupuesto): ?array
    {
        $seccion = trim((string) ($entry->seccion ?? $entry->Seccion ?? ''));
        $servicio = trim((string) ($entry->servicio ?? $entry->Servicio ?? ''));
        $programa = trim((string) ($entry->programa ?? $entry->Programa ?? ''));
        $capitulo = trim((string) ($entry->capitulo ?? $entry->Capitulo ?? ''));
        $articulo = trim((string) ($entry->articulo ?? $entry->Articulo ?? ''));
        $concepto = trim((string) ($entry->concepto ?? $entry->Concepto ?? ''));
        $importe = trim((string) ($entry->importe ?? $entry->Importe ?? $entry->creditos ?? ''));

        if ($importe === '') return null;

        $importe = str_replace(['.', ' '], '', $importe);
        $importe = str_replace(',', '.', $importe);

        return [
            'tipo_presupuesto' => $tipoPresupuesto,
            'codigo_organica' => $seccion ? ($seccion . ($servicio ? '.' . $servicio : '')) : null,
            'codigo_funcional' => $programa ?: null,
            'codigo_economica' => ($capitulo . $articulo . $concepto) ?: null,
            'credito_inicial' => (float) $importe,
            'fuente' => 'pge',
        ];
    }
}
