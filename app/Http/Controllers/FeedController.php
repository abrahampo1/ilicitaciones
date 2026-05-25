<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function rss(): Response
    {
        $xml = cache()->remember('analisis_feed_rss', 600, function () {
            $articles = Article::published()->latest('published_at')->limit(40)->get();

            $site = config('app.url');
            $now = now()->toRfc822String();

            $items = '';
            foreach ($articles as $a) {
                $url = route('analisis.show', $a->slug);
                $items .= "    <item>\n"
                    .'      <title>'.$this->esc($a->title)."</title>\n"
                    ."      <link>{$url}</link>\n"
                    ."      <guid isPermaLink=\"true\">{$url}</guid>\n"
                    .'      <pubDate>'.$a->published_at->toRfc822String()."</pubDate>\n"
                    .'      <description>'.$this->esc($a->dek ?? '')."</description>\n"
                    ."    </item>\n";
            }

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<rss version="2.0">'."\n"
                ."  <channel>\n"
                ."    <title>I-Licitaciones · Análisis</title>\n"
                ."    <link>{$site}/analisis</link>\n"
                ."    <description>Periodismo de datos sobre contratación pública en España.</description>\n"
                ."    <language>es-ES</language>\n"
                ."    <lastBuildDate>{$now}</lastBuildDate>\n"
                .$items
                ."  </channel>\n"
                .'</rss>';
        });

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
