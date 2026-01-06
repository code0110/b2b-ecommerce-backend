<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Page;
use App\Models\BlogPost;
use App\Models\Promotion;

class SitemapController extends Controller
{
    public function index(Request $request)
    {
        $base = rtrim(config('app.url') ?: $request->getSchemeAndHttpHost(), '/');
        $urls = [];
        $urls[] = [
            'loc' => $base . '/',
            'lastmod' => Carbon::now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];
        $urls[] = [
            'loc' => $base . '/produse',
            'lastmod' => Carbon::now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
        Category::where('is_published', true)->orderBy('updated_at', 'desc')->take(5000)->get()->each(function ($c) use (&$urls, $base) {
            $urls[] = [
                'loc' => $base . '/categorie/' . $c->slug,
                'lastmod' => optional($c->updated_at)->toAtomString() ?: Carbon::now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        });
        Product::where('status', 'published')->orderBy('updated_at', 'desc')->take(50000)->get()->each(function ($p) use (&$urls, $base) {
            $urls[] = [
                'loc' => $base . '/produs/' . $p->slug,
                'lastmod' => optional($p->updated_at)->toAtomString() ?: Carbon::now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        });
        Page::where('is_published', true)->orderBy('updated_at', 'desc')->take(1000)->get()->each(function ($pg) use (&$urls, $base) {
            $urls[] = [
                'loc' => $base . '/pagina/' . $pg->slug,
                'lastmod' => optional($pg->updated_at)->toAtomString() ?: Carbon::now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        });
        BlogPost::where('is_published', true)->orderBy('published_at', 'desc')->take(5000)->get()->each(function ($bp) use (&$urls, $base) {
            $urls[] = [
                'loc' => $base . '/blog/' . $bp->slug,
                'lastmod' => optional($bp->updated_at)->toAtomString() ?: Carbon::now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        });
        Promotion::where('status', 'published')->orderBy('updated_at', 'desc')->take(1000)->get()->each(function ($pr) use (&$urls, $base) {
            $urls[] = [
                'loc' => $base . '/promotii/' . $pr->slug,
                'lastmod' => optional($pr->updated_at)->toAtomString() ?: Carbon::now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        });
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($u['loc']) . '</loc>';
            if (!empty($u['lastmod'])) $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>';
            if (!empty($u['changefreq'])) $xml .= '<changefreq>' . $u['changefreq'] . '</changefreq>';
            if (!empty($u['priority'])) $xml .= '<priority>' . $u['priority'] . '</priority>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';
        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
