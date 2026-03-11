<?php

namespace Modules\Contratos\Console;

use Modules\Contratos\Models\Categoria;
use Illuminate\Console\Command;

class ImportarCategorias extends Command
{
    protected $signature = 'contratos:importar-categorias';

    protected $description = 'Importa categorias CPV desde XML externo';

    public function handle(): int
    {
        $this->info('Importando categorias desde XML...');

        $xml = simplexml_load_file('https://contrataciondelestado.es/codice/cl/1.04/CPV2007-1.04.gc');

        if (!$xml) {
            $this->error('No se pudo cargar el archivo XML.');
            return self::FAILURE;
        }

        $this->info('Procesando categorias...');

        $rows = $xml->SimpleCodeList->Row;
        $this->info($rows->count() . ' categorias encontradas.');

        $progressBar = $this->output->createProgressBar($rows->count());
        $progressBar->start();

        foreach ($rows as $categoria) {
            $nombre = (string) $categoria->Value[2]->SimpleValue;
            $xml_id = (int) $categoria->Value[0]->SimpleValue;
            Categoria::updateOrCreate(
                ['xml_id' => $xml_id],
                ['nombre' => $nombre]
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Importación de categorias completada.');

        return self::SUCCESS;
    }
}
