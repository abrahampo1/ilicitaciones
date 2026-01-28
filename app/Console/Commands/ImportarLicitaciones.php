<?php

namespace App\Console\Commands;

use App\Models\Adjudicacion;
use App\Models\Categoria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Noki\XmlConverter\Convert;
class ImportarLicitaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:importar-licitaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar licitaciones desde una fuente externa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Comienza la fiesta de importación

        $this->info("Iniciando la importación de licitaciones...");

        $all = $this->ask("¿Deseas importar todas las licitaciones desde el año 2012? (s/n)", "s");

        if (strtolower($all) === 'n') {
            $year = $this->ask("¿Que año deseas importar?", 2012);
            $this->importarLicitacion($year);
        }

        if (strtolower($all) === 's') {
            for ($year = 2012; $year <= date('Y'); $year++) {
                $this->importarLicitacion($year);
            }
        }
    }

    function importarLicitacion($year)
    {
        // Este url descarga un archivo zip con las licitaciones en formato XML (atom)
        $url = $this->retrieveUrl($year);
        $this->info("Importando licitaciones del año {$year} desde la URL: {$url}");
        $filename = "licitaciones_{$year}.zip";

        $file = file_get_contents($url);
        Storage::disk('local')->put('licitaciones/' . $filename, $file);
        $filePath = Storage::disk('local')->path('licitaciones/' . $filename);

        $this->info("Archivo descargado: {$filename}");

        $this->info("Ubicación del archivo: {$filePath}");
        // Descomprimimos el archivo zip en un directorio temporal
        $zip = new \ZipArchive;
        if ($zip->open($filePath) === TRUE) {
            $this->info("Descomprimiendo el archivo zip...");
            $extractPath = Storage::disk('local')->path('licitaciones/' . 'extract_' . $year);
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info("Archivo descomprimido en: {$extractPath}");

        } else {
            $this->error("No se pudo abrir el archivo zip.");
            return;
        }

        // Procesamos los archivos XML descomprimidos
        $files = glob($extractPath . '/*.atom');
        $fileProgress = 1;
        foreach ($files as $file) {
            $this->info("Procesando archivo {$fileProgress} de " . count($files) . ": {$file}");
            $this->newLine();
            $fileProgress++;
            $xmlContent = file_get_contents($file);
            $json = Convert::xmlToJson($xmlContent);

            // Comienza la fiesta de la importacion

            // Primero, iteramos sobre cada entrada
            $data = json_decode($json, true);

            $feed = $data['feed'] ?? null;
            if (!$feed) {
                $this->error("No se encontró el feed en el archivo: {$file}");
                continue;
            }

            $entries = $feed['entry'] ?? [];
            if (empty($entries)) {
                $this->info("No se encontraron entradas en el feed del archivo: {$file}");
                continue;
            }

            $progressBar = $this->output->createProgressBar(count($entries));
            $progressBar->start();

            foreach ($entries as $entry) {
                // Aquí procesamos cada entrada individualmente
                $titulo = $entry['title'] ?? null;
                $identificador = $entry['ContractFolderStatus']['ContractFolderID'] ?? null;
                $descripcion = $entry['ContractFolderStatus']['TenderingTerms']['Description'] ?? null;
                $estado = $entry['ContractFolderStatus']['ContractFolderStatusCode']['value'] ?? null;

                $importes = $entry['ContractFolderStatus']['ProcurementProject']['BudgetAmount'] ?? null;
                $importeTotal = $importes['TotalAmount']['value'] ?? null;
                $importeFinal = $importes['TaxExclusiveAmount']['value'] ?? null;
                $importeEstimado = $importes['EstimatedOverallContractAmount']['value'] ?? null;

                $fechaContratacion = $entry['ContractFolderStatus']['TenderResult']['Contract']['IssueDate'] ?? null;
                $fechaActualizacion = $entry['updated'] ?? null;


                $categoriaId = Categoria::where('xml_id', $entry['ContractFolderStatus']['ProcurementProject']['RequiredCommodityClassification']['ItemClassificationCode']['value'] ?? null)->first()->id ?? null;

                $organismo = $entry['ContractFolderStatus']['LocatedContractingParty']['Party'] ?? null;
                $organismoNombre = $organismo['PartyName']['Name'] ?? null;
                $organismoIdentificador = $organismo['PartyIdentification']['ID']['value'] ?? null;

                $organismoDireccion = $organismo['PostalAddress']['AddressLine']['Line'] ?? null;
                $organismoPais = $organismo['PostalAddress']['Country']['Name'] ?? null;
                $organismoProvincia = $organismo['PostalAddress']['CityName'] ?? null;
                $organismoCodigoPostal = $organismo['PostalAddress']['PostalZone'] ?? null;

                $organismoContacto = $organismo['Contact'] ?? null;
                $organismoContactoNombre = $organismoContacto['Name'] ?? null;
                $organismoContactoTelefono = $organismoContacto['Telephone'] ?? null;
                $organismoContactoFax = $organismoContacto['Telefax'] ?? null;
                $organismoContactoEmail = $organismoContacto['ElectronicMail'] ?? null;

                $organismoWebsite = $organismo['WebsiteURI'] ?? null;

                // Guardamos o actualizamos el organismo en la base de datos
                $organismoModel = \App\Models\Organismo::updateOrCreate(
                    ['identificador' => $organismoIdentificador],
                    [
                        'nombre' => $organismoNombre,
                        'direccion' => $organismoDireccion,
                        'pais' => $organismoPais,
                        'provincia' => $organismoProvincia,
                        'codigo_postal' => $organismoCodigoPostal,
                        'contacto_nombre' => $organismoContactoNombre,
                        'contacto_telefono' => $organismoContactoTelefono,
                        'contacto_fax' => $organismoContactoFax,
                        'contacto_email' => $organismoContactoEmail,
                        'sitio_web' => $organismoWebsite
                    ]
                );


                // Ahora creamos o actualizamos la empresa ganadora si existe
                $empresaGanadora = $entry['ContractFolderStatus']['TenderResult']['WinningParty'] ?? null;

                $empresaNombre = $empresaGanadora['PartyName']['Name'] ?? null;
                $empresaIdentificador = $empresaGanadora['PartyIdentification']['ID']['value'] ?? null;

                $importeCobradoEmpresa = $entry['ContractFolderStatus']['TenderResult']['AwardedTenderedProject']['LegalMonetaryTotal'] ?? null;

                $importeTotalEmpresa = $importeCobradoEmpresa['PayableAmount']['value'] ?? null;
                $importeTotalCobradoEmpresa = $importeCobradoEmpresa['TaxExclusiveAmount']['value'] ?? null;

                $urgenciaEmpresa = $entry['ContractFolderStatus']['TenderingProcess']['UrgencyCode']['value'] ?? null;
                $tipoProcedimientoEmpresa = $entry['ContractFolderStatus']['TenderingProcess']['ProcedureCode']['value'] ?? null;
                $descripcionEmpresa = $entry['ContractFolderStatus']['TenderResult']['Description'] ?? null;

                $fechaAdjudicacionEmpresa = $entry['ContractFolderStatus']['TenderResult']['Contract']['IssueDate'] ?? null;
                $fechaComienzoEmpresa = $entry['ContractFolderStatus']['TenderResult']['StartDate'] ?? null;

                $empresaModel = \App\Models\Empresa::updateOrCreate(
                    ['identificador' => $empresaIdentificador],
                    [
                        'nombre' => $empresaNombre,
                    ]
                );

                // Guardamos o actualizamos la licitación en la base de datos
                $licitacionModel = \App\Models\Licitacion::updateOrCreate(
                    ['identificador' => $identificador],
                    [
                        'titulo' => $titulo,
                        'descripcion' => $descripcion,
                        'estado' => $estado,
                        'importe_total' => $importeTotal,
                        'importe_final' => $importeFinal,
                        'importe_estimado' => $importeEstimado,
                        'fecha_contratacion' => $fechaContratacion,
                        'fecha_actualizacion' => $fechaActualizacion,
                        'categoria_id' => $categoriaId,
                        'organismo_id' => $organismoModel->id,
                        'datos_raiz' => json_encode($entry),
                        // Asignar otros campos según sea necesario
                    ]
                );

                // Finalmente, guardamos o actualizamos la adjudicación
                Adjudicacion::updateOrCreate(
                    [
                        'licitacion_id' => $licitacionModel->id,
                        'empresa_id' => $empresaModel->id,
                    ],
                    [
                        'importe' => $importeTotalEmpresa,
                        'importe_final' => $importeTotalCobradoEmpresa,
                        'urgencia' => $urgenciaEmpresa,
                        'tipo_procedimiento' => $tipoProcedimientoEmpresa,
                        'descripcion' => $descripcionEmpresa,
                        'fecha_adjudicacion' => $fechaAdjudicacionEmpresa,
                        'fecha_comienzo' => $fechaComienzoEmpresa,
                    ]
                );

                $progressBar->advance();
            }

            $progressBar->finish();
        }

        $this->info("\nImportación de licitaciones para el año {$year} completada.");
    }

    function retrieveUrl($year)
    {
        return "https://contrataciondelsectorpublico.gob.es/sindicacion/sindicacion_643/licitacionesPerfilesContratanteCompleto3_" . $year . ".zip";
    }
}
