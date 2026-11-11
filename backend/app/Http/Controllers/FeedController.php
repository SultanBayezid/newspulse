<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * RSS 2.0 feed of the latest published articles.
     */
    public function rss(): Response
    {
        $articles = Article::published()
            ->with(['category', 'author.avatar', 'cover'])
            ->orderByDesc('published_at')
            ->take(30)
            ->get();

        $siteName = config('app.name');
        $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $buildDate = now()->toRssString();

        $items = $articles->map(function (Article $article) use ($baseUrl, $siteName) {
            $link = $baseUrl . '/article/' . $article->slug;

            return <<<XML
            <item>
                <title>{$this->escape($article->title)}</title>
                <link>{$this->escape($link)}</link>
                <guid isPermaLink="false">newspulse-article-{$article->id}</guid>
                <description>{$this->escape($article->excerpt_text)}</description>
                <category>{$this->escape($article->category?->name ?? 'News')}</category>
                <author>{$this->escape($article->author?->name ?? $siteName)}</author>
                <pubDate>{$article->published_at?->toRssString()}</pubDate>
            </item>
            XML;
        })->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
            <channel>
                <title>{$this->escape($siteName)}</title>
                <link>{$this->escape($baseUrl)}</link>
                <description>{$this->escape($siteName)} - latest news</description>
                <language>en</language>
                <lastBuildDate>{$buildDate}</lastBuildDate>
                <atom:link href="{$this->escape($baseUrl . '/feed')}" rel="self" type="application/rss+xml" />
                {$items}
            </channel>
        </rss>
        XML;

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * XML sitemap for search engines.
     */
    public function sitemap(): Response
    {
        $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

        $urls = collect();

        $urls->push(['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'hourly']);

        Category::query()
            ->active()
            ->orderBy('position')
            ->get(['slug', 'updated_at'])
            ->each(fn (Category $category) => $urls->push([
                'loc' => $baseUrl . '/category/' . $category->slug,
                'priority' => '0.8',
                'changefreq' => 'daily',
            ]));

        Article::published()
            ->orderByDesc('published_at')
            ->take(1000)
            ->get(['slug', 'updated_at'])
            ->each(fn (Article $article) => $urls->push([
                'loc' => $baseUrl . '/article/' . $article->slug,
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $article->updated_at?->toAtomString(),
            ]));

        Page::published()->get(['slug', 'updated_at'])
            ->each(fn (Page $page) => $urls->push([
                'loc' => $baseUrl . '/page/' . $page->slug,
                'priority' => '0.4',
                'changefreq' => 'monthly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ]));

        $body = $urls->map(function (array $url) {
            $lastmod = isset($url['lastmod'])
                ? "\n        <lastmod>{$url['lastmod']}</lastmod>"
                : '';

            return <<<XML
            <url>
                <loc>{$this->escape($url['loc'])}</loc>
                <changefreq>{$url['changefreq']}</changefreq>{$lastmod}
                <priority>{$url['priority']}</priority>
            </url>
            XML;
        })->implode("\n");

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$body}
        </urlset>
        XML;

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function escape(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
