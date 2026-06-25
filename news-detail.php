<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: news.php');
    exit;
}

$db = DB::getInstance();
$news = $db->fetchOne("SELECT * FROM `news` WHERE `id` = ? AND `is_published` = 1", [$id]);

if (!$news) {
    header('HTTP/1.0 404 Not Found');
    $pageTitle = '文章不存在 - ' . getSetting('site_name');
    $siteDescription = '文章不存在或已下架';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container" style="text-align:center;padding:120px 20px"><div style="font-size:48px;margin-bottom:16px;opacity:0.5">📄</div><h2 style="color:#2C1810;margin-bottom:12px">文章不存在或已下架</h2><p style="color:#C4A882;margin-bottom:24px">该文章可能已被删除或暂时不可访问</p><a href="news.php" class="btn btn-primary" style="background:linear-gradient(135deg,#D4A843,#B8860B);color:#FFF9F0;display:inline-block">← 返回新闻列表</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// 增加阅读量
$db->execute("UPDATE `news` SET `view_count` = `view_count` + 1 WHERE `id` = ?", [$id]);
$viewCount = $news['view_count'] + 1;

$pageTitle = h($news['title']) . ' - ' . getSetting('site_name');
$siteDescription = $news['summary'] ?: truncate(strip_tags($news['content']), 150);

$catMap = ['company' => '公司动态', 'industry' => '行业资讯', 'product' => '产品更新'];
$catIcons = ['company' => '🏢', 'industry' => '📊', 'product' => '🚀'];

// 获取上/下一篇文章
$prevNews = $db->fetchOne(
    "SELECT `id`, `title` FROM `news` WHERE `is_published` = 1 AND `created_at` < ? ORDER BY `created_at` DESC LIMIT 1",
    [$news['created_at']]
);
$nextNews = $db->fetchOne(
    "SELECT `id`, `title` FROM `news` WHERE `is_published` = 1 AND `created_at` > ? ORDER BY `created_at` ASC LIMIT 1",
    [$news['created_at']]
);

// 获取相关文章（同分类）
$relatedNews = $db->fetchAll(
    "SELECT `id`, `title`, `cover_image`, `created_at`, `view_count` FROM `news` WHERE `is_published` = 1 AND `category` = ? AND `id` != ? ORDER BY `created_at` DESC LIMIT 3",
    [$news['category'], $id]
);

// 获取侧边栏文章列表（同分类 + 最近文章，排除当前）
$sidebarNews = $db->fetchAll(
    "SELECT `id`, `title`, `created_at`, `view_count`, `category` FROM `news` WHERE `is_published` = 1 AND `id` != ? ORDER BY `created_at` DESC LIMIT 15",
    [$id]
);

// AI 智能问答
require_once __DIR__ . '/includes/ai_config.php';
require_once __DIR__ . '/includes/ai_helper.php';

include __DIR__ . '/includes/header.php';
?>

<style>
/* ====== 文章详情页专用样式 ====== */

/* — 阅读进度条 — */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    z-index: 999;
    transition: width 0.1s linear;
}

/* — 文章英雄区 — */
.article-hero {
    position: relative;
    padding: 56px 0;
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 40%, #3C2418 100%);
    overflow: hidden;
    border-bottom: 3px solid rgba(212,168,67,0.3);
}
.article-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 50% at 30% 30%, rgba(212,168,67,0.05) 0%, transparent 60%),
        radial-gradient(ellipse 50% 70% at 70% 70%, rgba(232,213,163,0.03) 0%, transparent 60%);
    pointer-events: none;
    z-index: 1;
}
/* 有封面图模式 — 用封面图做背景 */
.article-hero.has-cover {
    min-height: 380px;
    padding: 0;
    display: flex;
    align-items: flex-end;
    border-bottom: none;
}
.article-hero.has-cover::before { display: none; }
.article-hero.has-cover .hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    z-index: 0;
}
.article-hero.has-cover .hero-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,15,10,0.85) 0%, rgba(26,15,10,0.5) 40%, rgba(26,15,10,0.3) 70%, rgba(26,15,10,0.6) 100%);
}
.article-hero.has-cover .hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #1A0F0A 0%, transparent 30%, rgba(26,15,10,0.2) 60%, transparent 100%);
    z-index: 1;
}
.article-hero .container {
    position: relative;
    z-index: 2;
    max-width: 800px;
}
.article-hero.has-cover .container {
    padding: 60px 20px 44px;
}
.article-hero .breadcrumb {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 20px;
    font-size: 13px;
    color: rgba(196,168,130,0.5);
}
.article-hero .breadcrumb a {
    color: rgba(212,168,67,0.7);
    text-decoration: none;
    transition: color 0.2s;
}
.article-hero .breadcrumb a:hover { color: #D4A843; }
.article-hero .breadcrumb span { color: rgba(196,168,130,0.4); }

.article-hero .cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 14px;
}
.article-hero .cat-badge.company { background: rgba(245,237,214,0.15); color: #E8D5A3; }
.article-hero .cat-badge.industry { background: rgba(245,237,214,0.15); color: #C4A882; }
.article-hero .cat-badge.product { background: rgba(245,237,214,0.15); color: #D4A843; }

.article-hero h1 {
    font-size: 28px;
    font-weight: 700;
    color: #E8D5A3;
    line-height: 1.35;
    margin-bottom: 16px;
    letter-spacing: 0.5px;
}
.article-hero.has-cover h1 {
    font-size: 30px;
    text-shadow: 0 2px 20px rgba(0,0,0,0.3);
}
.article-hero .article-meta {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    font-size: 13px;
    color: rgba(196,168,130,0.7);
}
.article-hero .article-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.article-hero .article-meta .divider {
    width: 1px;
    height: 14px;
    background: rgba(196,168,130,0.2);
}

/* — 阅读时间标签 — */
.read-time {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 10px;
    background: rgba(212,168,67,0.1);
    border: 1px solid rgba(212,168,67,0.2);
    border-radius: 4px;
    font-size: 12px;
    color: rgba(212,168,67,0.8);
}

/* — 导读卡片 — */
.summary-card {
    background: linear-gradient(135deg, #FFF9F0 0%, #F5EDD6 100%);
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 28px;
    position: relative;
    border: 1px solid #E8D5A3;
}
.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #D4A843, #B8860B);
    border-radius: 4px 0 0 4px;
}
.summary-card .summary-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #B8860B;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.summary-card p {
    color: #5C4033;
    font-size: 15px;
    line-height: 1.9;
    margin-bottom: 0;
}

/* — 文章内容 — */
.article-body {
    max-width: 740px;
    margin: 0 auto;
    padding: 0 20px;
}
.article-content {
    font-size: 16px;
    line-height: 2;
    color: #3C2418;
}
.article-content > *:first-child { margin-top: 0; }
.article-content p {
    margin-bottom: 18px;
}
.article-content h2 {
    font-size: 22px;
    font-weight: 700;
    color: #2C1810;
    margin: 36px 0 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #F5EDD6;
}
.article-content h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2C1810;
    margin: 28px 0 10px;
}
.article-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: #5C4033;
    margin: 24px 0 8px;
}
.article-content ul,
.article-content ol {
    margin: 0 0 18px 24px;
}
.article-content li {
    margin-bottom: 6px;
}
.article-content li::marker {
    color: #B8860B;
}
.article-content blockquote {
    margin: 24px 0;
    padding: 18px 24px;
    background: #F5EDD6;
    border-left: 4px solid #B8860B;
    border-radius: 0 8px 8px 0;
    color: #5C4033;
    font-style: italic;
    font-size: 15px;
}
.article-content blockquote p { margin-bottom: 0; }
.article-content img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    margin: 24px 0;
    border: 1px solid #E8D5A3;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.article-content a {
    color: #B8860B;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.article-content a:hover { color: #8B6914; }
.article-content strong { color: #2C1810; }
.article-content code {
    background: #F5EDD6;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 14px;
    color: #8B6914;
}
.article-content pre {
    background: #1A0F0A;
    color: #E8D5A3;
    padding: 20px 24px;
    border-radius: 10px;
    overflow-x: auto;
    font-size: 14px;
    line-height: 1.7;
    margin: 24px 0;
    border: 1px solid rgba(212,168,67,0.1);
}
.article-content pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: inherit;
}
.article-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #E8D5A3;
}
.article-content th {
    background: #2C1810;
    color: #E8D5A3;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    text-align: left;
}
.article-content td {
    padding: 10px 16px;
    border-top: 1px solid #F5EDD6;
    font-size: 14px;
}
.article-content tr:nth-child(even) { background: #FFF9F0; }

/* — 文章底部 — */
.article-footer {
    margin: 48px 0 0;
    padding: 0;
}
.article-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #E8D5A3;
}
.article-tags .tag-label {
    font-size: 13px;
    color: #8B7355;
    font-weight: 600;
}
.article-tags .tag-item {
    display: inline-block;
    padding: 4px 12px;
    background: #F5EDD6;
    border-radius: 4px;
    font-size: 12px;
    color: #8B6914;
}

/* — 文章导航 — */
.article-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 48px;
}
.article-nav a {
    display: flex;
    flex-direction: column;
    padding: 20px 24px;
    border-radius: 12px;
    border: 1px solid #E8D5A3;
    background: #FFF9F0;
    text-decoration: none;
    transition: all 0.25s ease;
}
.article-nav a:hover {
    border-color: #D4A843;
    box-shadow: 0 4px 16px rgba(184,134,11,0.08);
}
.article-nav .nav-label {
    font-size: 12px;
    color: #C4A882;
    margin-bottom: 6px;
    font-weight: 500;
}
.article-nav .nav-title {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
}
.article-nav a:first-child {
    padding-left: 36px;
}
.article-nav a:last-child {
    padding-right: 36px;
}
.article-nav .nav-next { text-align: right; }

/* — 相关文章 — */
.related-section {
    margin: 0;
    padding: 0 0 48px;
}
.related-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    padding-bottom: 14px;
    border-bottom: 2px solid #F5EDD6;
}
.related-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C1810;
}
.related-header .related-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, #E8D5A3, transparent);
}
.related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.related-card {
    display: flex;
    flex-direction: column;
    border-radius: 10px;
    overflow: hidden;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    text-decoration: none;
    color: inherit;
    transition: all 0.25s ease;
}
.related-card:hover {
    border-color: #D4A843;
    box-shadow: 0 6px 20px rgba(184,134,11,0.08);
    transform: translateY(-2px);
}
.related-card .rel-img {
    height: 120px;
    background: linear-gradient(135deg, #2C1810, #1A0F0A);
    background-size: cover;
    background-position: center;
}
.related-card .rel-body {
    padding: 14px 16px 16px;
    flex: 1;
}
.related-card .rel-body h4 {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
    margin-bottom: 8px;
}
.related-card .rel-body .rel-meta {
    font-size: 12px;
    color: #C4A882;
}

/* — 浮动操作按钮 — */
.float-back {
    position: fixed;
    bottom: 32px;
    left: 32px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border: none;
    box-shadow: 0 4px 16px rgba(184,134,11,0.3);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 18px;
    transition: all 0.25s ease;
    z-index: 50;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
}
.float-back.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
.float-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(184,134,11,0.45);
}

/* — 左侧文章导航栏 — */
.article-layout {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    gap: 32px;
    padding: 0 20px;
}
.article-sidebar {
    width: 240px;
    flex-shrink: 0;
    position: sticky;
    top: 24px;
    align-self: flex-start;
    max-height: calc(100vh - 48px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #E8D5A3 transparent;
}
.article-sidebar::-webkit-scrollbar { width: 4px; }
.article-sidebar::-webkit-scrollbar-thumb { background: #E8D5A3; border-radius: 2px; }
.sb-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #F5EDD6;
}
.sb-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #2C1810;
    margin: 0;
}
.sb-header .sb-count {
    font-size: 11px;
    color: #C4A882;
    background: #F5EDD6;
    padding: 1px 8px;
    border-radius: 10px;
    font-weight: 600;
}
.sb-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sb-list li { margin-bottom: 2px; }
.sb-list a {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}
.sb-list a:hover,
.sb-list a.active {
    background: #FFF9F0;
    border-left-color: #D4A843;
}
.sb-list a.active { background: #F5EDD6; }
.sb-list .sb-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #E8D5A3;
    flex-shrink: 0;
    margin-top: 6px;
    transition: background 0.2s;
}
.sb-list a:hover .sb-dot,
.sb-list a.active .sb-dot { background: #D4A843; }
.sb-list .sb-info { flex: 1; min-width: 0; }
.sb-list .sb-title {
    font-size: 13px;
    font-weight: 500;
    color: #5C4033;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
    transition: color 0.2s;
}
.sb-list a:hover .sb-title,
.sb-list a.active .sb-title { color: #2C1810; }
.sb-list .sb-meta {
    font-size: 11px;
    color: #C4A882;
    margin-top: 3px;
}
.sb-list .sb-active-indicator {
    font-size: 10px;
    color: #B8860B;
    font-weight: 700;
    background: #F5EDD6;
    padding: 0 6px;
    border-radius: 3px;
}
.article-main {
    flex: 1;
    min-width: 0;
}

/* — AI 智能问答 — */
.ai-section {
    margin-top: 40px;
    padding-top: 32px;
    border-top: 2px solid #F5EDD6;
}
.ai-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
}
.ai-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #FFF9F0, #F5EDD6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(212,168,67,0.1);
}
.ai-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C1810;
    margin: 0 0 3px;
}
.ai-header .ai-desc {
    font-size: 13px;
    color: #C4A882;
    margin: 0;
}
.ai-questions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 4px;
}
.ai-q-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 9px 18px;
    border-radius: 50px;
    border: 1px solid #E8D5A3;
    background: #FFF9F0;
    color: #5C4033;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.ai-q-btn:hover {
    border-color: #D4A843;
    background: linear-gradient(135deg, #FFF9F0, #F5EDD6);
    color: #2C1810;
    box-shadow: 0 2px 10px rgba(212,168,67,0.15);
    transform: translateY(-1px);
}
.ai-q-btn:active { transform: translateY(0); }
.ai-q-btn.loading {
    opacity: 0.6;
    pointer-events: none;
}
.ai-questions-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    color: #C4A882;
    font-size: 13px;
}
.ai-questions-loading .ai-spinner {
    width: 18px;
    height: 18px;
    border-width: 2px;
}
.ai-questions-ready {
    animation: aiFadeIn 0.4s ease;
}
@keyframes aiFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ai-answer-area {
    margin-top: 16px;
    padding: 20px 24px;
    background: linear-gradient(135deg, #FFF9F0, #F5EDD6);
    border-radius: 12px;
    border: 1px solid #E8D5A3;
    position: relative;
}
.ai-answer-area::before {
    content: '';
    position: absolute;
    top: -1px;
    left: 24px;
    right: 24px;
    height: 3px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    border-radius: 0 0 3px 3px;
}
.ai-answer {
    font-size: 15px;
    line-height: 1.9;
    color: #3C2418;
}
.ai-answer p { margin-bottom: 10px; }
.ai-answer p:last-child { margin-bottom: 0; }
.ai-answer strong { color: #2C1810; }
.ai-answer ul { margin: 8px 0 8px 20px; }
.ai-answer li { margin-bottom: 4px; }
.ai-loading {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 0;
    color: #C4A882;
    font-size: 14px;
}
.ai-spinner {
    width: 22px;
    height: 22px;
    border: 3px solid #E8D5A3;
    border-top-color: #D4A843;
    border-radius: 50%;
    animation: aiSpin 0.7s linear infinite;
}
@keyframes aiSpin { to { transform: rotate(360deg); } }
.ai-error {
    padding: 12px 16px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    border-radius: 8px;
    color: #DC2626;
    font-size: 14px;
    line-height: 1.6;
}

@media (max-width: 1023px) {
    .article-layout { max-width: 740px; flex-direction: column; }
    .article-sidebar {
        width: 100%;
        position: static;
        max-height: none;
        overflow: visible;
        margin-bottom: 8px;
    }
    .sb-list {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 8px;
        scrollbar-width: thin;
    }
    .sb-list li { flex-shrink: 0; margin-bottom: 0; }
    .sb-list a {
        white-space: nowrap;
        padding: 8px 14px;
        border-left: none;
        border-bottom: 2px solid transparent;
        border-radius: 6px;
        gap: 6px;
    }
    .sb-list a:hover,
    .sb-list a.active {
        border-left-color: transparent;
        border-bottom-color: #D4A843;
    }
    .sb-list .sb-dot { display: none; }
    .sb-list .sb-meta,
    .sb-list .sb-active-indicator { display: none; }
    .article-sidebar .sb-count { display: none; }
}

/* — 响应式 — */
@media (max-width: 768px) {
    .article-hero { padding: 36px 0 32px; }
    .article-hero.has-cover { min-height: 300px; }
    .article-hero h1 { font-size: 22px; }
    .article-hero.has-cover h1 { font-size: 24px; }
    .article-hero .breadcrumb { display: none; }
    .article-hero .article-meta { gap: 10px; font-size: 12px; }
    .article-hero .article-meta .divider { display: none; }
    .article-content { font-size: 15px; }
    .article-content h2 { font-size: 19px; }
    .article-body { padding: 0 16px; }
    .summary-card { padding: 18px 20px; }
    .article-footer { padding: 0 16px; }
    .article-nav { grid-template-columns: 1fr; }
    .article-nav .nav-next { text-align: left; }
    .related-grid { grid-template-columns: 1fr; }
    .float-back { left: 16px; bottom: 80px; width: 40px; height: 40px; }
}
@media (min-width: 769px) and (max-width: 1024px) {
    .related-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- ====== 阅读进度条 ====== -->
<div class="reading-progress" id="readingProgress"></div>

<!-- ====== 文章英雄区 ====== -->
<section class="article-hero<?= $news['cover_image'] ? ' has-cover' : '' ?>">
    <?php if ($news['cover_image']): ?>
    <div class="hero-bg" style="background-image:url('<?= h($news['cover_image']) ?>')"></div>
    <div class="hero-overlay"></div>
    <?php endif; ?>
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= SITE_URL ?>/index.php">首页</a>
            <span>›</span>
            <a href="<?= SITE_URL ?>/news.php">新闻动态</a>
            <span>›</span>
            <span><?= h($catMap[$news['category']] ?? $news['category']) ?></span>
        </div>
        <span class="cat-badge <?= h($news['category']) ?>">
            <?= $catIcons[$news['category']] ?? '' ?> <?= h($catMap[$news['category']] ?? $news['category']) ?>
        </span>
        <h1><?= h($news['title']) ?></h1>
        <div class="article-meta">
            <span>📅 <?= date('Y年m月d日', strtotime($news['created_at'])) ?></span>
            <span class="divider"></span>
            <span>👁️ <?= $viewCount ?> 次阅读</span>
            <?php
            // 估算阅读时间：按每分钟400字计算
            $textLen = mb_strlen(strip_tags($news['content']));
            $readMin = max(1, ceil($textLen / 400));
            ?>
            <span class="divider"></span>
            <span class="read-time">⏱ <?= $readMin ?> 分钟阅读</span>
            <?php if ($news['updated_at'] && $news['updated_at'] !== $news['created_at']): ?>
            <span class="divider"></span>
            <span>🔄 更新于 <?= date('Y-m-d', strtotime($news['updated_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ====== 文章正文 ====== -->
<section class="section" style="padding:36px 0 32px">
    <div class="article-layout">
        <!-- 左侧导航栏 -->
        <aside class="article-sidebar">
            <div class="sb-header">
                <h3>📰 所有文章</h3>
                <span class="sb-count"><?= count($sidebarNews) ?></span>
            </div>
            <ul class="sb-list">
                <?php foreach ($sidebarNews as $sb): ?>
                <li>
                    <a href="news-detail.php?id=<?= $sb['id'] ?>"<?= $sb['id'] == $id ? ' class="active"' : '' ?>>
                        <span class="sb-dot"></span>
                        <span class="sb-info">
                            <span class="sb-title"><?= h($sb['title']) ?></span>
                            <span class="sb-meta"><?= date('m-d', strtotime($sb['created_at'])) ?></span>
                        </span>
                        <?php if ($sb['id'] == $id): ?>
                        <span class="sb-active-indicator">当前</span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <!-- 右侧正文 -->
        <div class="article-main">
            <?php if ($news['summary']): ?>
            <div class="summary-card">
                <div class="summary-label">📌 文章导读</div>
                <p><?= h($news['summary']) ?></p>
            </div>
            <?php endif; ?>
            <div class="article-content">
                <?= $news['content'] ?>
            </div>

            <!-- ====== AI 智能问答（内容列内） ====== -->
            <div class="ai-section" id="aiSection">
                <div class="ai-header">
                    <div class="ai-header-icon">💡</div>
                    <div>
                        <h3>AI 智能问答</h3>
                        <p class="ai-desc">基于本文内容，AI 自动为您解答</p>
                    </div>
                </div>
                <div class="ai-questions" id="aiQuestions">
                    <div class="ai-questions-loading" id="aiQuestionsLoading">
                        <div class="ai-spinner"></div>
                        <span>AI 正在分析文章，生成智能问题...</span>
                    </div>
                </div>
                <div class="ai-answer-area" id="aiAnswerArea" style="display:none">
                    <div class="ai-loading" id="aiLoading" style="display:none">
                        <div class="ai-spinner"></div>
                        <span>AI 思考中...</span>
                    </div>
                    <div class="ai-error" id="aiError" style="display:none"></div>
                    <div class="ai-answer" id="aiAnswer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== 文章底部 ====== -->
    <aside class="article-footer">
        <!-- 文章导航 -->
        <div class="article-nav">
            <div>
                <?php if ($prevNews): ?>
                <a href="news-detail.php?id=<?= $prevNews['id'] ?>">
                    <span class="nav-label">← 上一篇</span>
                    <span class="nav-title"><?= h($prevNews['title']) ?></span>
                </a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($nextNews): ?>
                <a href="news-detail.php?id=<?= $nextNews['id'] ?>" class="nav-next">
                    <span class="nav-label">下一篇 →</span>
                    <span class="nav-title"><?= h($nextNews['title']) ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 返回列表 -->
        <div style="text-align:center;margin-bottom:40px">
            <a href="news.php" class="btn btn-primary" style="background:linear-gradient(135deg,#D4A843,#B8860B);color:#FFF9F0;display:inline-flex;align-items:center;gap:6px;padding:11px 28px;border-radius:50px">
                ← 返回新闻列表
            </a>
        </div>
    </aside>

    <!-- ====== 相关文章 ====== -->
    <?php if (!empty($relatedNews)): ?>
    <section class="related-section">
        <div class="related-header">
            <h3>📖 相关文章</h3>
            <div class="related-line"></div>
        </div>
        <div class="related-grid">
            <?php foreach ($relatedNews as $rel): ?>
            <a href="news-detail.php?id=<?= $rel['id'] ?>" class="related-card">
                <div class="rel-img" style="<?= $rel['cover_image'] ? 'background-image:url(' . h($rel['cover_image']) . ')' : 'background:linear-gradient(135deg,#2C1810,#1A0F0A)' ?>"></div>
                <div class="rel-body">
                    <h4><?= h($rel['title']) ?></h4>
                    <div class="rel-meta">📅 <?= date('Y-m-d', strtotime($rel['created_at'])) ?> · 👁️ <?= $rel['view_count'] ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</section>

<!-- ====== 浮动返回按钮 ====== -->
<a href="news.php" class="float-back" id="floatBackBtn" title="返回新闻列表">←</a>

<script>
// 阅读进度条
(function() {
    var bar = document.getElementById('readingProgress');
    var fb = document.getElementById('floatBackBtn');
    window.addEventListener('scroll', function() {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var progress = docHeight > 0 ? (scrollTop / docHeight * 100) : 0;
        if (bar) bar.style.width = Math.min(progress, 100) + '%';
        if (fb) {
            if (scrollTop > 300) fb.classList.add('visible');
            else fb.classList.remove('visible');
        }
    });
})();

// AI 智能问答
(function() {
    var nid = <?= $id ?>;
    var qContainer = document.getElementById('aiQuestions');
    var loadingHint = document.getElementById('aiQuestionsLoading');
    var area = document.getElementById('aiAnswerArea');
    var qLoading = document.getElementById('aiLoading');
    var errorDiv = document.getElementById('aiError');
    var answerDiv = document.getElementById('aiAnswer');
    if (!qContainer || !area) return;

    var answeredSet = {};

    function renderQuestions(questions) {
        qContainer.innerHTML = '';
        questions.forEach(function(q, i) {
            var btn = document.createElement('button');
            btn.className = 'ai-q-btn';
            btn.textContent = q;
            btn.setAttribute('data-qi', i);
            if (answeredSet[i]) btn.classList.add('ai-q-answered');
            btn.addEventListener('click', function() {
                askQuestion(q, i);
            });
            qContainer.appendChild(btn);
        });
        qContainer.classList.add('ai-questions-ready');
    }

    function askQuestion(question, idx) {
        var btns = qContainer.querySelectorAll('.ai-q-btn');
        btns.forEach(function(b) { b.classList.add('loading'); });

        qLoading.style.display = 'flex';
        errorDiv.style.display = 'none';
        answerDiv.innerHTML = '';
        area.style.display = 'block';

        var fd = new FormData();
        fd.append('id', nid);
        fd.append('type', 'news');
        fd.append('question', question);

        fetch('/ajax/ai_answer.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            qLoading.style.display = 'none';
            btns.forEach(function(b) { b.classList.remove('loading'); });
            if (data.success) {
                var html = data.data.answer
                    .replace(/### (.+)/g, '<h3>$1</h3>')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n- /g, '<br>• ')
                    .replace(/\n/g, '<br>');
                answerDiv.innerHTML = '<p>' + html.replace(/^<br>/,'') + '</p>';
                answeredSet[idx] = true;
                if (btns[idx]) btns[idx].classList.add('ai-q-answered');
            } else {
                errorDiv.textContent = data.error || '请求失败，请稍后重试';
                errorDiv.style.display = 'block';
            }
        })
        .catch(function(err) {
            qLoading.style.display = 'none';
            errorDiv.textContent = '网络错误，请检查后重试';
            errorDiv.style.display = 'block';
            btns.forEach(function(b) { b.classList.remove('loading'); });
        });
    }

    var qfd = new FormData();
    qfd.append('id', nid);
    qfd.append('type', 'news');

    fetch('/ajax/ai_questions.php', { method: 'POST', body: qfd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.data && data.data.questions && data.data.questions.length > 0) {
            renderQuestions(data.data.questions);
        } else {
            renderQuestions([
                '这篇文章主要讲了什么？',
                '对行业有什么影响？',
                '未来趋势如何？',
                '有哪些关键信息？',
                '适合哪些人群阅读？',
                '有什么实际应用？',
                '与其他方案比怎么样？',
                '数据来源可靠吗？',
                '下一步该怎么看？',
                '有什么行动建议？',
            ]);
        }
    })
    .catch(function() {
        renderQuestions([
            '这篇文章主要讲了什么？',
            '对行业有什么影响？',
            '未来趋势如何？',
            '有哪些关键信息？',
            '适合哪些人群阅读？',
            '有什么实际应用？',
            '与其他方案比怎么样？',
            '数据来源可靠吗？',
            '下一步该怎么看？',
            '有什么行动建议？',
        ]);
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
