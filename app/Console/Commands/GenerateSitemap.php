<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Empresa;
use App\Models\Licitacion;
use App\Models\Organismo;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml for SEO';

    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        // Static pages
        $xml .= $this->url($baseUrl, 'daily', '1.0');
        $xml .= $this->url($baseUrl.'/organismos', 'daily', '0.8');
        $xml .= $this->url($baseUrl.'/empresas', 'daily', '0.8');
        $xml .= $this->url($baseUrl.'/analisis', 'daily', '0.8');

        // Análisis publicados (el producto editorial: prioridad por encima de las fichas).
        Article::published()->select('slug', 'updated_at')->chunk(500, function ($articles) use (&$xml, $baseUrl) {
            foreach ($articles as $article) {
                $xml .= $this->url(
                    $baseUrl.'/analisis/'.$article->slug,
                    'weekly',
                    '0.7',
                    $article->updated_at?->toW3cString()
                );
            }
        });

        // Organismos
        Organismo::select('id', 'updated_at')->chunk(500, function ($organismos) use (&$xml, $baseUrl) {
            foreach ($organismos as $organismo) {
                $xml .= $this->url(
                    $baseUrl.'/organismo/'.$organismo->id,
                    'weekly',
                    '0.6',
                    $organismo->updated_at?->toW3cString()
                );
            }
        });

        // Empresas
        Empresa::select('id', 'updated_at')->chunk(500, function ($empresas) use (&$xml, $baseUrl) {
            foreach ($empresas as $empresa) {
                $xml .= $this->url(
                    $baseUrl.'/empresa/'.$empresa->id,
                    'weekly',
                    '0.6',
                    $empresa->updated_at?->toW3cString()
                );
            }
        });

        // Licitaciones
        Licitacion::select('id', 'updated_at')->chunk(500, function ($licitaciones) use (&$xml, $baseUrl) {
            foreach ($licitaciones as $licitacion) {
                $xml .= $this->url(
                    $baseUrl.'/licitacion/'.$licitacion->id,
                    'weekly',
                    '0.6',
                    $licitacion->updated_at?->toW3cString()
                );
            }
        });

        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap generated at public/sitemap.xml');

        return Command::SUCCESS;
    }

    private function url(string $loc, string $changefreq, string $priority, ?string $lastmod = null): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>{$loc}</loc>\n";
        if ($lastmod) {
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "  </url>\n";

        return $xml;
    }
}
