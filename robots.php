<?php
/**
 * robots.txt - 搜索引擎爬虫规则
 * 动态生成，包含动态的 Sitemap URL
 */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Allow: /
Sitemap: <?= SITE_URL ?>/sitemap.php
