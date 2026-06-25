<?php
$pageTitle = '文档中心';
$siteDescription = '查找产品文档、API 手册、操作指南和最佳实践，快速上手使用我们的服务。';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

$db = DB::getInstance();

// 获取所有分类（树形）
$allCats = $db->fetchAll("SELECT * FROM doc_categories ORDER BY sort_order ASC, id ASC");
$catTree = [];
$catMap = [];
foreach ($allCats as $c) {
    $c['children'] = [];
    $catMap[$c['id']] = $c;
}
foreach ($allCats as $c) {
    if ($c['parent_id'] > 0 && isset($catMap[$c['parent_id']])) {
        $catMap[$c['parent_id']]['children'][] = &$catMap[$c['id']];
    } else {
        $catTree[] = &$catMap[$c['id']];
    }
}
unset($c);

// 获取所有已发布文章
$allArticles = $db->fetchAll("SELECT * FROM doc_articles WHERE is_published = 1 ORDER BY sort_order ASC, created_at DESC");
$articlesByCat = [];
foreach ($allArticles as $a) {
    $articlesByCat[$a['category_id']][] = $a;
}

// 当前选中的文章
$articleId = intval($_GET['id'] ?? 0);
$currentArticle = null;
if ($articleId > 0) {
    $currentArticle = $db->fetchOne("SELECT a.*, c.name as category_name FROM doc_articles a LEFT JOIN doc_categories c ON a.category_id = c.id WHERE a.id = ? AND a.is_published = 1", [$articleId]);
    if ($currentArticle) {
        $db->execute("UPDATE doc_articles SET view_count = view_count + 1 WHERE id = ?", [$articleId]);
    }
}

// 递归标记活跃路径
function markActivePath(&$cats, $articlesByCat, $activeId) {
    $anyActive = false;
    foreach ($cats as &$cat) {
        $cat['hasActive'] = false;
        if ($activeId > 0 && isset($articlesByCat[$cat['id']])) {
            foreach ($articlesByCat[$cat['id']] as $a) {
                if ($a['id'] == $activeId) { $cat['hasActive'] = true; break; }
            }
        }
        if (!empty($cat['children'])) {
            if (markActivePath($cat['children'], $articlesByCat, $activeId)) $cat['hasActive'] = true;
        }
        if ($cat['hasActive']) $anyActive = true;
    }
    return $anyActive;
}
markActivePath($catTree, $articlesByCat, $articleId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文档中心</title>
    <style>
/* ====== 文档中心全局样式 ====== */

/* — 英雄区 — */
.doc-hero {
    position: relative;
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 50%, #3C2418 100%);
    padding: 56px 0 48px;
    overflow: hidden;
    border-bottom: 3px solid rgba(212,168,67,0.3);
}
.doc-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 20%, rgba(212,168,67,0.06) 0%, transparent 70%),
        radial-gradient(ellipse 60% 80% at 80% 80%, rgba(232,213,163,0.04) 0%, transparent 70%);
    pointer-events: none;
}
.doc-hero .container { position: relative; z-index: 1; }
.doc-hero h1 {
    font-size: 32px;
    font-weight: 700;
    color: #E8D5A3;
    margin-bottom: 8px;
    letter-spacing: 1px;
}
.doc-hero p {
    font-size: 15px;
    color: rgba(196,168,130,0.75);
    max-width: 520px;
    margin-bottom: 20px;
    line-height: 1.7;
}

/* — 搜索栏 — */
.doc-search-wrap {
    position: relative;
    max-width: 480px;
}
.doc-search-wrap .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(196,168,130,0.5);
    font-size: 16px;
    pointer-events: none;
    z-index: 1;
}
.doc-search-wrap input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    background: rgba(255,249,240,0.08);
    border: 1.5px solid rgba(212,168,67,0.2);
    border-radius: 8px;
    color: #E8D5A3;
    font-size: 14px;
    outline: none;
    transition: all 0.25s;
    box-sizing: border-box;
}
.doc-search-wrap input::placeholder {
    color: rgba(196,168,130,0.4);
}
.doc-search-wrap input:focus {
    border-color: rgba(212,168,67,0.5);
    background: rgba(255,249,240,0.12);
    box-shadow: 0 0 0 3px rgba(212,168,67,0.08);
}
.doc-search-wrap .clear-btn {
    display: none;
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(196,168,130,0.4);
    cursor: pointer;
    font-size: 16px;
    padding: 4px;
}
.doc-search-wrap .clear-btn.visible { display: block; }
.doc-search-wrap .clear-btn:hover { color: #C4A882; }

/* — 主布局 — */
.doc-layout {
    display: flex;
    min-height: calc(100vh - 200px);
    background: #FFF9F0;
}

/* ====== 侧边栏 ====== */
.doc-sidebar {
    width: 280px;
    min-width: 280px;
    background: linear-gradient(180deg, #1A0F0A 0%, #2C1810 100%);
    border-right: 1px solid rgba(184,134,11,0.2);
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: hidden;
}
.doc-sidebar-header {
    padding: 20px 18px 14px;
    border-bottom: 1px solid rgba(184,134,11,0.15);
    flex-shrink: 0;
}
.doc-sidebar-header h2 {
    margin: 0 0 2px 0;
    font-size: 15px;
    color: #E8D5A3;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.doc-sidebar-header .doc-count {
    font-size: 12px;
    color: rgba(196,168,130,0.5);
    font-weight: 400;
}
.doc-sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 6px 0 20px;
    scrollbar-width: thin;
    scrollbar-color: rgba(184,134,11,0.2) transparent;
}
.doc-sidebar-body::-webkit-scrollbar { width: 4px; }
.doc-sidebar-body::-webkit-scrollbar-track { background: transparent; }
.doc-sidebar-body::-webkit-scrollbar-thumb { background: rgba(184,134,11,0.2); border-radius: 2px; }

/* — 树形导航 — */
.doc-tree { list-style: none; padding: 0; margin: 0; }
.doc-tree ul { list-style: none; padding: 0; margin: 0; }

.tree-cat-label {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px 7px 18px;
    cursor: pointer;
    color: rgba(196,168,130,0.7);
    font-size: 13px;
    transition: all 0.15s;
    user-select: none;
    border-left: 2px solid transparent;
}
.tree-cat-label:hover {
    background: rgba(184,134,11,0.08);
    color: #C4A882;
}
.tree-cat-label .tree-toggle {
    font-size: 8px;
    color: rgba(184,134,11,0.5);
    width: 14px;
    text-align: center;
    flex-shrink: 0;
    transition: transform 0.2s;
}
.tree-cat-label .tree-toggle.expanded { transform: rotate(0deg); }
.tree-cat-label .tree-toggle.collapsed { transform: rotate(-90deg); }
.tree-cat-label .tree-cat-icon {
    font-size: 14px;
    flex-shrink: 0;
}
.tree-cat-label .tree-cat-name {
    flex: 1;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tree-cat-label .tree-cat-name.level0 { font-weight: 600; color: #E8D5A3; }
.tree-cat-label .tree-cat-name.level1 { color: #C4A882; }
.tree-cat-label .tree-cat-name.level2 { color: rgba(196,168,130,0.8); }
.tree-cat-label .tree-count {
    font-size: 10px;
    background: rgba(184,134,11,0.12);
    color: rgba(196,168,130,0.5);
    padding: 1px 6px;
    border-radius: 8px;
    flex-shrink: 0;
}

.tree-children { padding-left: 16px; }

.tree-articles { list-style: none; padding: 0; margin: 0 0 2px; }
.tree-article { position: relative; }
.tree-article a {
    position: relative;
    display: block;
    padding: 5px 14px 5px 20px;
    color: rgba(196,168,130,0.6);
    font-size: 13px;
    text-decoration: none;
    transition: all 0.15s;
    border-left: 2px solid transparent;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tree-article a:hover {
    color: #E8D5A3;
    background: rgba(184,134,11,0.06);
    border-left-color: rgba(212,168,67,0.3);
}
.tree-article.active a {
    color: #FFF9F0;
    background: linear-gradient(90deg, rgba(184,134,11,0.2), transparent);
    border-left-color: #D4A843;
    font-weight: 600;
}
.tree-article.active a::before {
    content: '';
    position: absolute;
    left: -2px;
    top: 4px;
    bottom: 4px;
    width: 2px;
    background: linear-gradient(180deg, #D4A843, #B8860B);
    border-radius: 0 2px 2px 0;
}

/* 搜索高亮 */
.tree-article.highlight a {
    color: #E8D5A3;
    background: rgba(212,168,67,0.08);
}
.tree-category.highlight > .tree-cat-label {
    color: #E8D5A3;
}

/* ====== 内容区 ====== */
.doc-content-area {
    flex: 1;
    padding: 36px 48px;
    overflow-y: auto;
    max-width: 900px;
    min-width: 0;
}

.doc-content-area .breadcrumb {
    display: flex;
    gap: 6px;
    align-items: center;
    font-size: 13px;
    color: #C4A882;
    margin-bottom: 20px;
}
.doc-content-area .breadcrumb a {
    color: #B8860B;
    text-decoration: none;
    transition: color 0.2s;
}
.doc-content-area .breadcrumb a:hover { color: #8B6914; text-decoration: underline; }
.doc-content-area .breadcrumb .sep {
    color: #E8D5A3;
    font-size: 12px;
}

.doc-article-title {
    font-size: 26px;
    font-weight: 700;
    color: #2C1810;
    line-height: 1.35;
    margin: 0 0 12px;
}
.doc-article-meta {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    font-size: 13px;
    color: #C4A882;
    padding-bottom: 16px;
    border-bottom: 1px solid #E8D5A3;
    margin-bottom: 28px;
}
.doc-article-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}
.doc-article-meta .meta-divider {
    width: 1px;
    height: 12px;
    background: #E8D5A3;
}

/* — 文章正文 — */
.doc-body {
    font-size: 15px;
    line-height: 2;
    color: #3C2418;
}
.doc-body > *:first-child { margin-top: 0; }
.doc-body h1 {
    font-size: 24px;
    font-weight: 700;
    color: #2C1810;
    margin: 32px 0 14px;
    padding-bottom: 10px;
    border-bottom: 2px solid #F5EDD6;
}
.doc-body h2 {
    font-size: 20px;
    font-weight: 600;
    color: #2C1810;
    margin: 28px 0 10px;
}
.doc-body h3 {
    font-size: 17px;
    font-weight: 600;
    color: #5C4033;
    margin: 24px 0 8px;
}
.doc-body p { margin: 0 0 14px; }
.doc-body ul, .doc-body ol { margin: 8px 0 14px; padding-left: 24px; }
.doc-body li { margin-bottom: 4px; }
.doc-body li::marker { color: #B8860B; }
.doc-body strong { color: #2C1810; }
.doc-body code {
    background: #F5EDD6;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 13px;
    color: #8B6914;
}
.doc-body pre {
    background: #1A0F0A;
    color: #E8D5A3;
    padding: 18px 20px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 16px 0;
    border: 1px solid rgba(212,168,67,0.08);
    font-size: 13px;
    line-height: 1.7;
}
.doc-body pre code {
    background: none;
    padding: 0;
    color: inherit;
    font-size: inherit;
}
.doc-body blockquote {
    margin: 16px 0;
    padding: 12px 20px;
    background: #F5EDD6;
    border-left: 4px solid #B8860B;
    border-radius: 0 6px 6px 0;
    color: #5C4033;
    font-style: italic;
}
.doc-body blockquote p { margin-bottom: 0; }
.doc-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #E8D5A3;
}
.doc-body th {
    background: #2C1810;
    color: #E8D5A3;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    text-align: left;
}
.doc-body td {
    padding: 8px 14px;
    border-top: 1px solid #F5EDD6;
    font-size: 14px;
}
.doc-body tr:nth-child(even) { background: #FFF9F0; }
.doc-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 16px 0;
    border: 1px solid #E8D5A3;
}
.doc-body a {
    color: #B8860B;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.doc-body a:hover { color: #8B6914; }
.doc-body hr {
    border: none;
    border-top: 1px solid #E8D5A3;
    margin: 24px 0;
}

/* — 阅读进度条 — */
.doc-reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    z-index: 999;
    transition: width 0.1s linear;
}

/* — 底部导航 — */
.doc-footer-nav {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #E8D5A3;
}
.doc-footer-nav a {
    display: flex;
    flex-direction: column;
    padding: 14px 20px;
    border-radius: 10px;
    border: 1px solid #E8D5A3;
    background: #FFF9F0;
    text-decoration: none;
    transition: all 0.25s;
    max-width: 45%;
}
.doc-footer-nav a:hover {
    border-color: #D4A843;
    box-shadow: 0 4px 16px rgba(184,134,11,0.06);
}
.doc-footer-nav a:only-child { max-width: 100%; align-items: center; }
.doc-footer-nav .fn-label {
    font-size: 11px;
    color: #C4A882;
    margin-bottom: 4px;
    font-weight: 500;
    letter-spacing: 0.5px;
}
.doc-footer-nav .fn-title {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.doc-footer-nav .fn-next { text-align: right; margin-left: auto; }

/* ====== 欢迎页 ====== */
.doc-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 60px 20px;
    min-height: calc(100vh - 300px);
}
.doc-welcome-icon {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: linear-gradient(135deg, #F5EDD6, #FFF9F0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    margin-bottom: 24px;
    border: 2px solid #E8D5A3;
    box-shadow: 0 8px 24px rgba(184,134,11,0.08);
}
.doc-welcome h2 {
    font-size: 24px;
    font-weight: 700;
    color: #2C1810;
    margin: 0 0 8px;
}
.doc-welcome p {
    font-size: 15px;
    color: #C4A882;
    margin: 0 0 32px;
    max-width: 400px;
    line-height: 1.7;
}
.doc-welcome-cats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    width: 100%;
    max-width: 640px;
}
.doc-welcome-cat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 16px;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.25s;
}
.doc-welcome-cat:hover {
    border-color: #D4A843;
    box-shadow: 0 8px 24px rgba(184,134,11,0.08);
    transform: translateY(-2px);
}
.doc-welcome-cat .wc-icon {
    font-size: 28px;
    margin-bottom: 8px;
}
.doc-welcome-cat .wc-name {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
}
.doc-welcome-cat .wc-count {
    font-size: 12px;
    color: #C4A882;
    margin-top: 2px;
}

/* ====== 空状态 ====== */
.doc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: #C4A882;
}
.doc-empty-icon { font-size: 48px; margin-bottom: 12px; opacity: 0.5; }
.doc-empty h3 { font-size: 18px; color: #2C1810; margin: 0 0 6px; }
.doc-empty p { font-size: 14px; color: #C4A882; margin: 0; }

/* ====== 移动端 ====== */
.doc-mobile-header {
    display: none;
    padding: 12px 16px;
    background: #1A0F0A;
    border-bottom: 1px solid rgba(184,134,11,0.2);
    align-items: center;
    gap: 12px;
}
.doc-mobile-header .menu-btn {
    background: none;
    border: 1px solid rgba(212,168,67,0.3);
    border-radius: 6px;
    color: #C4A882;
    padding: 6px 10px;
    cursor: pointer;
    font-size: 16px;
}
.doc-mobile-header .menu-btn:hover {
    background: rgba(184,134,11,0.1);
}
.doc-mobile-header h2 {
    margin: 0;
    font-size: 15px;
    color: #E8D5A3;
    font-weight: 600;
    flex: 1;
}
.doc-sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    z-index: 998;
}

@media (max-width: 768px) {
    .doc-hero { padding: 40px 0 32px; }
    .doc-hero h1 { font-size: 24px; }
    .doc-sidebar {
        position: fixed;
        left: -300px;
        top: 0;
        bottom: 0;
        z-index: 999;
        transition: left 0.3s ease;
        box-shadow: 4px 0 20px rgba(0,0,0,0.2);
    }
    .doc-sidebar.mobile-open { left: 0; }
    .doc-sidebar-overlay.show { display: block; }
    .doc-content-area { padding: 20px 16px; }
    .doc-article-title { font-size: 20px; }
    .doc-welcome-cats { grid-template-columns: 1fr; }
    .doc-mobile-header { display: flex; }
    .doc-layout { flex-direction: column; }
    .doc-footer-nav { flex-direction: column; }
    .doc-footer-nav a { max-width: 100%; }
    .doc-footer-nav .fn-next { text-align: left; margin-left: 0; }
}

/* — 搜索结果高亮 — */
.search-match {
    background: rgba(212,168,67,0.15);
    color: #C4A882;
    padding: 0 2px;
    border-radius: 2px;
}

/* — 加载动画 — */
.doc-body {
    animation: docFadeIn 0.3s ease;
}
@keyframes docFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
    </style>
</head>
<body>

<!-- 阅读进度条 -->
<div class="doc-reading-progress" id="docProgress"></div>

<!-- 移动端顶部栏 -->
<div class="doc-mobile-header">
    <button class="menu-btn" onclick="toggleDocSidebar()">☰</button>
    <h2>📖 文档中心</h2>
</div>

<!-- ====== 英雄区 ====== -->
<section class="doc-hero">
    <div class="container">
        <h1>📖 文档中心</h1>
        <p>查找产品文档、API 手册、操作指南和最佳实践，快速上手使用我们的服务。</p>
        <div class="doc-search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" id="docSearch" placeholder="搜索文档标题..." oninput="filterDocs(this.value)">
            <button class="clear-btn" id="searchClear" onclick="clearSearch()">✕</button>
        </div>
    </div>
</section>

<!-- 侧边栏遮罩 -->
<div class="doc-sidebar-overlay" id="sidebarOverlay" onclick="toggleDocSidebar()"></div>

<!-- ====== 主布局 ====== -->
<div class="doc-layout">
    <!-- 侧边栏 -->
    <aside class="doc-sidebar" id="docSidebar">
        <div class="doc-sidebar-header">
            <h2>📂 文档目录</h2>
            <div class="doc-count">共 <?= count($allArticles) ?> 篇文章</div>
        </div>
        <div class="doc-sidebar-body" id="docSidebarBody">
            <?php
            // 构建树形 HTML
            function renderTree($cats, $articlesByCat, $activeId = 0, $depth = 0) {
                $icons = ['📁', '📂', '📄'];
                $html = '<ul class="doc-tree">';
                foreach ($cats as $cat) {
                    $hasChildren = !empty($cat['children']);
                    $catArticles = $articlesByCat[$cat['id']] ?? [];
                    $expand = ($cat['hasActive'] ?? false);
                    $html .= '<li class="tree-category" data-catid="' . $cat['id'] . '">';
                    $html .= '<div class="tree-cat-label" onclick="toggleTree(this)">';
                    $html .= '<span class="tree-toggle ' . ($expand ? 'expanded' : 'collapsed') . '">&#9660;</span>';
                    $html .= '<span class="tree-cat-icon">' . ($icons[min($depth, 2)] ?? '📄') . '</span>';
                    $html .= '<span class="tree-cat-name level' . min($depth, 2) . '">' . h($cat['name']) . '</span>';
                    $html .= '<span class="tree-count">' . count($catArticles) . '</span>';
                    $html .= '</div>';
                    $html .= '<div class="tree-children"' . ($expand || !empty($_GET['id']) ? '' : ' style="display:none"') . '>';
                    if ($hasChildren) {
                        $html .= renderTree($cat['children'], $articlesByCat, $activeId, $depth + 1);
                    }
                    if ($catArticles) {
                        $html .= '<ul class="tree-articles">';
                        foreach ($catArticles as $a) {
                            $isActive = $a['id'] == $activeId;
                            $html .= '<li class="tree-article' . ($isActive ? ' active' : '') . '" data-title="' . h($a['title']) . '">';
                            $html .= '<a href="docs.php?id=' . $a['id'] . '">' . h($a['title']) . '</a>';
                            $html .= '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '</div>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
                return $html;
            }
            echo renderTree($catTree, $articlesByCat, $currentArticle ? $currentArticle['id'] : 0);
            ?>
        </div>
    </aside>

    <!-- 内容区 -->
    <main class="doc-content-area" id="docContentArea">
        <?php if ($currentArticle):
            // 获取前后文章
            $allOrdered = $db->fetchAll(
                "SELECT a.id, a.title FROM doc_articles a JOIN doc_categories c ON a.category_id = c.id WHERE a.is_published = 1 ORDER BY c.sort_order, a.sort_order, a.created_at DESC"
            );
            $prevDoc = null;
            $nextDoc = null;
            foreach ($allOrdered as $i => $d) {
                if ($d['id'] == $currentArticle['id']) {
                    if ($i > 0) $prevDoc = $allOrdered[$i - 1];
                    if ($i < count($allOrdered) - 1) $nextDoc = $allOrdered[$i + 1];
                    break;
                }
            }
        ?>
        <div class="breadcrumb">
            <a href="docs.php">文档中心</a>
            <span class="sep">›</span>
            <span><?= h($currentArticle['category_name']) ?></span>
        </div>
        <h1 class="doc-article-title"><?= h($currentArticle['title']) ?></h1>
        <div class="doc-article-meta">
            <span>📅 <?= date('Y-m-d', strtotime($currentArticle['created_at'])) ?></span>
            <span class="meta-divider"></span>
            <span>👁️ <?= $currentArticle['view_count'] ?> 次阅读</span>
            <span class="meta-divider"></span>
            <span>⏱ <?= max(1, ceil(mb_strlen(strip_tags($currentArticle['content'])) / 400)) ?> 分钟阅读</span>
            <?php if ($currentArticle['updated_at'] && $currentArticle['updated_at'] !== $currentArticle['created_at']): ?>
            <span class="meta-divider"></span>
            <span>🔄 更新于 <?= date('Y-m-d', strtotime($currentArticle['updated_at'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="doc-body">
            <?= $currentArticle['content'] ?>
        </div>
        <div class="doc-footer-nav">
            <?php if ($prevDoc): ?>
            <a href="docs.php?id=<?= $prevDoc['id'] ?>">
                <span class="fn-label">← 上一篇</span>
                <span class="fn-title"><?= h($prevDoc['title']) ?></span>
            </a>
            <?php endif; ?>
            <?php if ($nextDoc): ?>
            <a href="docs.php?id=<?= $nextDoc['id'] ?>" class="fn-next">
                <span class="fn-label">下一篇 →</span>
                <span class="fn-title"><?= h($nextDoc['title']) ?></span>
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- 欢迎页 -->
        <div class="doc-welcome">
            <div class="doc-welcome-icon">📖</div>
            <h2>欢迎来到文档中心</h2>
            <p>从左侧目录选择一篇文档开始阅读，或使用搜索框快速查找。</p>
            <?php if (!empty($catTree)): ?>
            <div class="doc-welcome-cats">
                <?php foreach ($catTree as $topCat):
                    $catCnt = count($articlesByCat[$topCat['id']] ?? []);
                ?>
                <div class="doc-welcome-cat" onclick="document.querySelector('.tree-cat-label')?.click()">
                    <span class="wc-icon">📁</span>
                    <span class="wc-name"><?= h($topCat['name']) ?></span>
                    <span class="wc-count"><?= $catCnt ?> 篇文章</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
// 搜索过滤
function filterDocs(query) {
    var items = document.querySelectorAll('.tree-article');
    var cats = document.querySelectorAll('.tree-category');
    var clearBtn = document.getElementById('searchClear');
    query = query.trim().toLowerCase();
    
    if (query.length === 0) {
        items.forEach(function(el) { el.classList.remove('highlight'); el.style.display = ''; });
        cats.forEach(function(el) { el.classList.remove('highlight'); });
        clearBtn.classList.remove('visible');
        return;
    }
    clearBtn.classList.add('visible');
    
    items.forEach(function(el) {
        var title = (el.getAttribute('data-title') || '').toLowerCase();
        var match = title.indexOf(query) !== -1;
        el.classList.toggle('highlight', match);
        el.style.display = match ? '' : 'none';
    });
    
    // 展开所有匹配的父级
    items.forEach(function(el) {
        if (el.style.display !== 'none') {
            var parent = el.closest('.tree-children');
            while (parent) {
                parent.style.display = '';
                parent = parent.parentElement ? parent.parentElement.querySelector(':scope > .tree-children') : null;
            }
        }
    });
}

function clearSearch() {
    document.getElementById('docSearch').value = '';
    filterDocs('');
    document.getElementById('docSearch').focus();
}

// 树形折叠
function toggleTree(el) {
    var children = el.nextElementSibling;
    if (!children) return;
    var isHidden = children.style.display === 'none';
    children.style.display = isHidden ? '' : 'none';
    var toggle = el.querySelector('.tree-toggle');
    if (toggle) {
        toggle.className = 'tree-toggle ' + (isHidden ? 'expanded' : 'collapsed');
    }
}

// 阅读进度条
(function() {
    var bar = document.getElementById('docProgress');
    if (!bar) return;
    window.addEventListener('scroll', function() {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (docHeight > 0) {
            bar.style.width = Math.min((scrollTop / docHeight * 100), 100) + '%';
        }
    });
})();

// 侧边栏切换
function toggleDocSidebar() {
    var sidebar = document.getElementById('docSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('show');
}

// 页面加载后滚动到当前激活文章
(function() {
    var active = document.querySelector('.tree-article.active');
    if (active) {
        var sidebar = document.getElementById('docSidebarBody');
        active.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
