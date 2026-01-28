<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportarCategorias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:importar-categorias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa categorias desde un archivo XML externo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // aqui hay un xml https://contrataciondelestado.es/codice/cl/1.04/CPV2007-1.04.gc con las categorias

        $this->info('Importando categorias desde XML...');
        // vamos a descargar el xml y lo importamos a la base de datos
        $xml = simplexml_load_file('https://contrataciondelestado.es/codice/cl/1.04/CPV2007-1.04.gc');

        if (!$xml) {
            $this->error('No se pudo cargar el archivo XML.');
            return;
        }

        $this->info('Procesando categorias...');

        $xml = $xml->SimpleCodeList->Row;

        $this->info($xml->count() . ' categorias encontradas.');

        $progressBar = $this->output->createProgressBar($xml->count());
        $progressBar->start();

        foreach ($xml as $categoria) {
            $nombre = (string) $categoria->Value[2]->SimpleValue;
            $xml_id = (int) $categoria->Value[0]->SimpleValue;
            \App\Models\Categoria::updateOrCreate(
                ['xml_id' => $xml_id],
                ['nombre' => $nombre]
            );
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->info('Importación de categorias completada.');
    }
}
