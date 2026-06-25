<?php
/**
 * 站点地图 (XML Sitemap)
 * 自动生成所有公开页面的 sitemap
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$baseUrl = SITE_URL;

header('Content-Type: application/xml; charset=utf-8');

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 静态页面
$staticPages = [
    '' => 1.0,
    'news.php' => 0.8,
    'projects.php' => 0.8,
    'docs.php' => 0.8,
    'agent-list.php' => 0.7,
    'apply.php' => 0.7,
    'recruitment.php' => 0.6,
    'alliance.php' => 0.6,
    'query.php' => 0.5,
];

foreach ($staticPages as $path => $priority) {
    $url = $baseUrl . '/' . $path;
    $xml .= "  <url>\n";
    $xml .= "    <loc>{$url}</loc>\n";
    $xml .= "    <priority>{$priority}</priority>\n";
    $xml .= "  </url>\n";
}

// 新闻详情
try {
    $db = DB::getInstance();
    $news = $db->fetchAll("SELECT `id`, `updated_at` FROM `news` WHERE `status` = 1 ORDER BY `id` DESC");
    foreach ($news as $item) {
        $url = $baseUrl . '/news-detail.php?id=' . $item['id'];
        $lastmod = $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : '';
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url}</loc>\n";
        if ($lastmod) $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <priority>0.6</priority>\n";
        $xml .= "  </url>\n";
    }
} catch (Exception $e) {}

// 文档详情
try {
    $docs = $db->fetchAll("SELECT `id`, `updated_at` FROM `agent_docs` WHERE `status` = 1 ORDER BY `id` DESC");
    foreach ($docs as $item) {
        $url = $baseUrl . '/docs.php?id=' . $item['id'];
        $lastmod = $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : '';
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$url}</loc>\n";
        if ($lastmod) $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <priority>0.6</priority>\n";
        $xml .= "  </url>\n";
    }
} catch (Exception $e) {}

$xml .= '</urlset>';

echo $xml;
