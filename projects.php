<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = DB::getInstance();
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$project = null;

if ($projectId > 0) {
    // 详情模式
    require_once __DIR__ . '/includes/ai_config.php';
    require_once __DIR__ . '/includes/ai_helper.php';
    try {
        $rows = $db->fetchAll("SELECT * FROM `project_recommendations` WHERE `id` = ? AND `is_published` = 1", [$projectId]);
        $project = $rows[0] ?? null;
    } catch (Exception $e) {
        $project = null;
    }
    if (!$project) {
        header('Location: projects.php');
        exit;
    }
    $pageTitle = h($project['title']) . ' - 项目推荐 - ' . getSetting('site_name');

    // 获取侧边栏项目列表（排除当前）
    $sidebarProjects = $db->fetchAll(
        "SELECT `id`, `title`, `client_type`, `created_at` FROM `project_recommendations` WHERE `is_published` = 1 AND `id` != ? ORDER BY `sort_order` ASC, `id` DESC LIMIT 15",
        [$projectId]
    );
} else {
    try {
        $allCases = $db->fetchAll("SELECT * FROM `project_recommendations` WHERE `is_published` = 1 ORDER BY `sort_order` ASC, `id` DESC");
    } catch (Exception $e) {
        $allCases = [];
    }
    $pageTitle = '项目推荐 - ' . getSetting('site_name');
}

$isAgent = isset($_SESSION['agent_id']);
$freeLimit = intval(getSetting('project_free_limit') ?: '3');
$totalCases = $projectId > 0 ? 0 : count($allCases);

include __DIR__ . '/includes/header.php';
?>

<style>
.projects-hero {
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 100%);
    padding: 60px 0;
    text-align: center;
    color: #F5EDD6;
    border-bottom: 3px solid #B8860B;
}
.projects-hero h1 {
    font-size: 34px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #E8D5A3;
}
.projects-hero p {
    font-size: 16px;
    color: #C4A882;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.7;
}
.projects-stats {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(184,134,11,0.2);
    border-radius: 8px;
    padding: 20px 36px;
    margin-top: 30px;
    display: inline-flex;
    gap: 40px;
}
.stat-item {
    text-align: center;
}
.stat-number {
    font-size: 34px;
    font-weight: 700;
    color: #D4A843;
}
.stat-label {
    font-size: 13px;
    color: #C4A882;
    margin-top: 2px;
}
/* ===== 卡片列表（案例风格） ===== */
.case-card {
    display: block;
    text-decoration: none;
    color: inherit;
    background: #FFF;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #E8D5A3;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(184,134,11,0.06);
}
.case-card:hover {
    border-color: #D4A843;
    box-shadow: 0 8px 30px rgba(184,134,11,0.15);
    transform: translateY(-4px);
}
.case-image {
    height: 180px;
    background: linear-gradient(135deg, #F5EDD6 0%, #E8D5A3 100%);
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    padding: 12px;
}
.case-category {
    display: inline-block;
    background: rgba(44,24,16,0.75);
    color: #E8D5A3;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 1px;
    backdrop-filter: blur(4px);
}
.case-body {
    padding: 20px;
}
.case-title {
    font-size: 18px;
    font-weight: 700;
    color: #2C1810;
    margin-bottom: 8px;
    line-height: 1.4;
}
.case-desc {
    font-size: 14px;
    color: #8B7355;
    line-height: 1.7;
    margin-bottom: 16px;
}
.case-metrics {
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 100%);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.metric-num {
    font-size: 28px;
    font-weight: 800;
    color: #D4A843;
    line-height: 1.2;
    letter-spacing: 1px;
}
.metric-label {
    font-size: 13px;
    color: #C4A882;
    margin-top: 4px;
    letter-spacing: 2px;
}
.case-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.case-tag {
    display: inline-block;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 4px;
    background: #F5EDD6;
    color: #8B6914;
}
.projects-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.projects-grid .case-card {
    position: relative;
}
.projects-grid .case-card.locked::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255,255,255,0.7);
    z-index: 10;
    pointer-events: none;
    border-radius: 12px;
}
.lock-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 12px;
}
.lock-content {
    text-align: center;
    padding: 20px;
}
.lock-icon {
    display: inline-block;
    width: 32px;
    height: 24px;
    border: 3px solid #B8860B;
    border-radius: 4px;
    position: relative;
    margin-bottom: 12px;
}
.lock-icon::before {
    content: '';
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    width: 16px;
    height: 12px;
    border: 3px solid #B8860B;
    border-bottom: none;
    border-radius: 10px 10px 0 0;
}
.lock-title {
    font-size: 16px;
    font-weight: 700;
    color: #2C1810;
    margin-bottom: 8px;
}
.lock-desc {
    font-size: 13px;
    color: #8B7355;
    line-height: 1.6;
}
.lock-cta {
    display: inline-block;
    margin-top: 12px;
    padding: 8px 24px;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.lock-cta:hover { background: linear-gradient(135deg, #E8D5A3, #D4A843); color: #1A0F0A; }
@media (max-width: 1024px) {
    .projects-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .projects-grid { grid-template-columns: 1fr; }
    .projects-hero h1 { font-size: 24px; }
    .projects-hero p { font-size: 14px; }
}
/* ===== 详情页（全新） ===== */

/* — 阅读进度条 — */
.project-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    z-index: 999;
    transition: width 0.1s linear;
}

/* — 英雄区 — */
.project-hero {
    position: relative;
    min-height: 360px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 50%, #3C2418 100%);
    border-bottom: 3px solid rgba(212,168,67,0.3);
}
.project-hero .hero-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    z-index: 0;
}
.project-hero .hero-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,15,10,0.88) 0%, rgba(26,15,10,0.5) 40%, rgba(26,15,10,0.25) 70%, rgba(26,15,10,0.55) 100%);
}
.project-hero .hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #1A0F0A 0%, transparent 30%, rgba(26,15,10,0.15) 60%, transparent 100%);
    z-index: 1;
}
.project-hero .container {
    position: relative;
    z-index: 2;
    padding: 60px 20px 44px;
    max-width: 800px;
    margin: 0 auto;
}
.project-hero .breadcrumb {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 16px;
    font-size: 13px;
    color: rgba(196,168,130,0.5);
}
.project-hero .breadcrumb a {
    color: rgba(212,168,67,0.7);
    text-decoration: none;
    transition: color 0.2s;
}
.project-hero .breadcrumb a:hover { color: #D4A843; }
.project-hero .breadcrumb span { color: rgba(196,168,130,0.4); }

.project-hero .hero-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.project-hero .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.project-hero .hero-badge.client {
    background: rgba(245,237,214,0.15);
    color: #E8D5A3;
}
.project-hero .hero-badge.metric {
    background: rgba(212,168,67,0.15);
    color: #D4A843;
    border: 1px solid rgba(212,168,67,0.2);
}

.project-hero h1 {
    font-size: 30px;
    font-weight: 700;
    color: #E8D5A3;
    line-height: 1.35;
    margin-bottom: 14px;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 20px rgba(0,0,0,0.3);
}
.project-hero .article-meta {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
    font-size: 13px;
    color: rgba(196,168,130,0.7);
}
.project-hero .article-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.project-hero .article-meta .divider {
    width: 1px;
    height: 14px;
    background: rgba(196,168,130,0.2);
}

/* — 正文区 — */
.project-body-wrap {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

/* — 导读卡片 — */
.project-summary {
    background: linear-gradient(135deg, #FFF9F0 0%, #F5EDD6 100%);
    border-radius: 12px;
    padding: 24px 28px;
    margin-bottom: 28px;
    position: relative;
    border: 1px solid #E8D5A3;
}
.project-summary::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #D4A843, #B8860B);
    border-radius: 4px 0 0 4px;
}
.project-summary .summary-label {
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
.project-summary p {
    color: #5C4033;
    font-size: 15px;
    line-height: 1.9;
    margin-bottom: 0;
}

/* — 项目正文 — */
.project-content {
    font-size: 15px;
    color: #3C2418;
    line-height: 2;
    margin-bottom: 30px;
}
.project-content p { margin-bottom: 16px; }
.project-content h2, .project-content h3 {
    color: #2C1810;
    margin: 28px 0 12px;
}
.project-content h2 { font-size: 20px; font-weight: 700; }
.project-content h3 { font-size: 17px; font-weight: 600; }
.project-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 16px 0;
    border: 1px solid #E8D5A3;
}
.project-content ul, .project-content ol {
    padding-left: 24px;
    margin-bottom: 16px;
}
.project-content li { margin-bottom: 6px; }
.project-content li::marker { color: #B8860B; }
.project-content blockquote {
    border-left: 4px solid #D4A843;
    background: #FFF9F0;
    padding: 14px 22px;
    margin: 16px 0;
    border-radius: 0 8px 8px 0;
    color: #5C4033;
    font-style: italic;
}
.project-content blockquote p { margin-bottom: 0; }
.project-content code {
    background: #F5EDD6;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 13px;
    color: #8B6914;
}
.project-content pre {
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
.project-content pre code {
    background: none; padding: 0; color: inherit; font-size: inherit;
}
.project-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #E8D5A3;
}
.project-content th {
    background: #2C1810;
    color: #E8D5A3;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 600;
    text-align: left;
}
.project-content td {
    padding: 8px 14px;
    border-top: 1px solid #F5EDD6;
    font-size: 14px;
}
.project-content tr:nth-child(even) { background: #FFF9F0; }
.project-content a {
    color: #B8860B;
    text-decoration: underline;
    text-underline-offset: 2px;
}

/* — 标签 — */
.project-tags-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 36px;
    padding-top: 20px;
    border-top: 1px solid #E8D5A3;
}
.project-tag-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    padding: 5px 14px;
    border-radius: 6px;
    background: #F5EDD6;
    color: #8B6914;
    transition: all 0.2s;
}
.project-tag-item:hover { background: #E8D5A3; }

/* — 底部导航 — */
.project-nav {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 32px;
}
.project-nav a {
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
.project-nav a:hover {
    border-color: #D4A843;
    box-shadow: 0 4px 16px rgba(184,134,11,0.06);
}
.project-nav .pn-label {
    font-size: 11px;
    color: #C4A882;
    margin-bottom: 4px;
    font-weight: 500;
    letter-spacing: 0.5px;
}
.project-nav .pn-title {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.project-nav .pn-next { text-align: right; margin-left: auto; }

/* — 返回按钮 — */
.project-float-back {
    position: fixed;
    left: 24px;
    bottom: 32px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border: none;
    cursor: pointer;
    font-size: 18px;
    box-shadow: 0 4px 16px rgba(184,134,11,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.25s;
    opacity: 0;
    pointer-events: none;
    z-index: 100;
}
.project-float-back.visible {
    opacity: 1;
    pointer-events: auto;
}
.project-float-back:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 24px rgba(184,134,11,0.35);
}

/* — 相关推荐 — */
.related-projects {
    margin-top: 36px;
    padding-top: 28px;
    border-top: 1px solid #E8D5A3;
}
.related-projects h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C1810;
    margin-bottom: 16px;
}
.related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.related-card {
    display: block;
    text-decoration: none;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.25s;
}
.related-card:hover {
    border-color: #D4A843;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(184,134,11,0.08);
}
.related-card .rc-img {
    height: 120px;
    background-size: cover;
    background-position: center;
    background-color: #F5EDD6;
}
.related-card .rc-body {
    padding: 12px 14px;
}
.related-card .rc-title {
    font-size: 14px;
    font-weight: 600;
    color: #2C1810;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.related-card .rc-client {
    font-size: 12px;
    color: #C4A882;
}

/* — 左侧项目导航栏 — */
.project-layout {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    gap: 32px;
    padding: 0 20px;
}
.project-sidebar {
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
.project-sidebar::-webkit-scrollbar { width: 4px; }
.project-sidebar::-webkit-scrollbar-thumb { background: #E8D5A3; border-radius: 2px; }
.ps-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #F5EDD6;
}
.ps-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #2C1810;
    margin: 0;
}
.ps-header .ps-count {
    font-size: 11px;
    color: #C4A882;
    background: #F5EDD6;
    padding: 1px 8px;
    border-radius: 10px;
    font-weight: 600;
}
.ps-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.ps-list li { margin-bottom: 2px; }
.ps-list a {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}
.ps-list a:hover,
.ps-list a.active {
    background: #FFF9F0;
    border-left-color: #D4A843;
}
.ps-list a.active { background: #F5EDD6; }
.ps-list .ps-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #E8D5A3;
    flex-shrink: 0;
    margin-top: 6px;
    transition: background 0.2s;
}
.ps-list a:hover .ps-dot,
.ps-list a.active .ps-dot { background: #D4A843; }
.ps-list .ps-info { flex: 1; min-width: 0; }
.ps-list .ps-title {
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
.ps-list a:hover .ps-title,
.ps-list a.active .ps-title { color: #2C1810; }
.ps-list .ps-meta {
    font-size: 11px;
    color: #C4A882;
    margin-top: 3px;
}
.ps-list .ps-active-indicator {
    font-size: 10px;
    color: #B8860B;
    font-weight: 700;
    background: #F5EDD6;
    padding: 0 6px;
    border-radius: 3px;
}
.project-main {
    flex: 1;
    min-width: 0;
}

@media (max-width: 1023px) {
    .project-layout { max-width: 740px; flex-direction: column; }
    .project-sidebar {
        width: 100%;
        position: static;
        max-height: none;
        overflow: visible;
        margin-bottom: 8px;
    }
    .ps-list {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 8px;
        scrollbar-width: thin;
    }
    .ps-list li { flex-shrink: 0; margin-bottom: 0; }
    .ps-list a {
        white-space: nowrap;
        padding: 8px 14px;
        border-left: none;
        border-bottom: 2px solid transparent;
        border-radius: 6px;
        gap: 6px;
    }
    .ps-list a:hover,
    .ps-list a.active {
        border-left-color: transparent;
        border-bottom-color: #D4A843;
    }
    .ps-list .ps-dot { display: none; }
    .ps-list .ps-meta,
    .ps-list .ps-active-indicator { display: none; }
    .project-sidebar .ps-count { display: none; }
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
.ai-answered-count {
    font-size: 12px;
    color: #C4A882;
    margin-left: auto;
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
.ai-q-answered {
    background: linear-gradient(135deg, #F5EDD6, #E8D5A3) !important;
    border-color: #D4A843 !important;
    color: #2C1810 !important;
}

@media (max-width: 640px) {
    .ai-questions { gap: 8px; }
    .ai-q-btn { font-size: 12px; padding: 7px 14px; }
    .ai-answer-area { padding: 16px 18px; }
    .ai-answer { font-size: 14px; }
}

/* — 响应式 — */
@media (max-width: 768px) {
    .project-hero { min-height: 300px; }
    .project-hero h1 { font-size: 22px; }
    .project-hero .breadcrumb { display: none; }
    .project-hero .article-meta { gap: 10px; font-size: 12px; }
    .project-hero .article-meta .divider { display: none; }
    .project-body-wrap { padding: 0 16px; }
    .project-content { font-size: 14px; }
    .project-summary { padding: 18px 20px; }
    .project-nav { flex-direction: column; }
    .project-nav a { max-width: 100%; }
    .project-nav .pn-next { text-align: left; margin-left: 0; }
    .related-grid { grid-template-columns: 1fr; }
    .project-float-back { left: 16px; bottom: 80px; width: 40px; height: 40px; }
}
</style>

<?php if ($projectId > 0 && $project): ?>
<!-- ===== 详情模式 ===== -->
<div class="project-progress" id="projectProgress"></div>

<section class="project-hero">
    <?php if ($project['cover_image']): ?>
    <div class="hero-bg" style="background-image:url('<?= h($project['cover_image']) ?>')"></div>
    <div class="hero-overlay"></div>
    <?php endif; ?>
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= SITE_URL ?>/index.php">首页</a>
            <span>›</span>
            <a href="<?= SITE_URL ?>/projects.php">项目推荐</a>
            <span>›</span>
            <span><?= h($project['client_type'] ?: '项目详情') ?></span>
        </div>
        <div class="hero-badges">
            <span class="hero-badge client">🏢 <?= h($project['client_type'] ?: '精选项目') ?></span>
            <?php if ($project['metrics']): ?>
            <span class="hero-badge metric">📊 <?= h($project['metrics']) ?></span>
            <?php endif; ?>
            <?php
            $readMin = max(1, ceil(mb_strlen(strip_tags($project['content'])) / 400));
            ?>
            <span class="hero-badge metric">⏱ 阅读 <?= $readMin ?> 分钟</span>
        </div>
        <h1><?= h($project['title']) ?></h1>
        <div class="article-meta">
            <span>📅 <?= date('Y-m-d', strtotime($project['created_at'])) ?></span>
            <span class="divider"></span>
            <span>👁️ <?= ($project['view_count'] ?? 0) ?> 次阅读</span>
        </div>
    </div>
</section>

<section class="section" style="padding:36px 0 32px">
    <div class="project-layout">
        <!-- 左侧导航栏 -->
        <aside class="project-sidebar">
            <div class="ps-header">
                <h3>💼 所有项目</h3>
                <span class="ps-count"><?= count($sidebarProjects) ?></span>
            </div>
            <ul class="ps-list">
                <?php foreach ($sidebarProjects as $sp): ?>
                <li>
                    <a href="?id=<?= $sp['id'] ?>"<?= $sp['id'] == $project['id'] ? ' class="active"' : '' ?>>
                        <span class="ps-dot"></span>
                        <span class="ps-info">
                            <span class="ps-title"><?= h($sp['title']) ?></span>
                            <span class="ps-meta"><?= h($sp['client_type'] ?: '精选项目') ?></span>
                        </span>
                        <?php if ($sp['id'] == $project['id']): ?>
                        <span class="ps-active-indicator">当前</span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <!-- 右侧正文 -->
        <div class="project-main">
            <?php if ($project['description']): ?>
            <div class="project-summary">
                <div class="summary-label">📌 项目概览</div>
                <p><?= h($project['description']) ?></p>
            </div>
            <?php endif; ?>

            <div class="project-content"><?= $project['content'] ?></div>

            <?php
            // 获取前后项目
            $allOrdered = $db->fetchAll("SELECT * FROM project_recommendations WHERE is_published = 1 ORDER BY sort_order ASC, id DESC");
            $prevProj = null;
            $nextProj = null;
            foreach ($allOrdered as $i => $p) {
                if ($p['id'] == $project['id']) {
                    if ($i > 0) $prevProj = $allOrdered[$i - 1];
                    if ($i < count($allOrdered) - 1) $nextProj = $allOrdered[$i + 1];
                    break;
                }
            }
            ?>

            <?php if ($project['tags']): ?>
            <div class="project-tags-wrap">
                <?php foreach (explode(',', $project['tags']) as $tag): ?>
                <span class="project-tag-item"># <?= h(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 上下篇导航 -->
            <div class="project-nav">
                <?php if ($prevProj): ?>
                <a href="?id=<?= $prevProj['id'] ?>">
                    <span class="pn-label">← 上一篇</span>
                    <span class="pn-title"><?= h($prevProj['title']) ?></span>
                </a>
                <?php endif; ?>
                <?php if ($nextProj): ?>
                <a href="?id=<?= $nextProj['id'] ?>" class="pn-next">
                    <span class="pn-label">下一篇 →</span>
                    <span class="pn-title"><?= h($nextProj['title']) ?></span>
                </a>
                <?php endif; ?>
            </div>

            <!-- ====== AI 智能问答 ====== -->
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

            <!-- 相关推荐 -->
            <?php
            $related = [];
            $tagArr = array_map('trim', explode(',', $project['tags'] ?: ''));
            foreach ($allOrdered as $p) {
                if ($p['id'] == $project['id']) continue;
                if (count($related) >= 3) break;
                $pTags = array_map('trim', explode(',', $p['tags'] ?: ''));
                if (array_intersect($tagArr, $pTags)) {
                    $related[] = $p;
                }
            }
            if (count($related) < 3) {
                foreach ($allOrdered as $p) {
                    if ($p['id'] == $project['id']) continue;
                    if (count($related) >= 3) break;
                    $found = false;
                    foreach ($related as $r) { if ($r['id'] == $p['id']) { $found = true; break; } }
                    if (!$found) $related[] = $p;
                }
            }
            if ($related): ?>
            <div class="related-projects">
                <h3>相关项目推荐</h3>
                <div class="related-grid">
                    <?php foreach ($related as $rp): ?>
                    <a href="?id=<?= $rp['id'] ?>" class="related-card">
                        <div class="rc-img"<?= $rp['cover_image'] ? ' style="background-image:url(' . h($rp['cover_image']) . ')"' : '' ?>></div>
                        <div class="rc-body">
                            <div class="rc-title"><?= h($rp['title']) ?></div>
                            <div class="rc-client"><?= h($rp['client_type'] ?: '精选项目') ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<a class="project-float-back" id="floatBack" href="projects.php" title="返回列表">←</a>

<script>
(function() {
    // 进度条
    var bar = document.getElementById('projectProgress');
    if (bar) {
        window.addEventListener('scroll', function() {
            var st = window.scrollY || document.documentElement.scrollTop;
            var dh = document.documentElement.scrollHeight - window.innerHeight;
            if (dh > 0) bar.style.width = Math.min(st / dh * 100, 100) + '%';
        });
    }
    // 浮动返回按钮
    var fb = document.getElementById('floatBack');
    if (fb) {
        window.addEventListener('scroll', function() {
            fb.classList.toggle('visible', (window.scrollY || document.documentElement.scrollTop) > 300);
        });
    }
    // AI 智能问答
    (function() {
        var pid = <?= $projectId ?>;
        var qContainer = document.getElementById('aiQuestions');
        var loadingHint = document.getElementById('aiQuestionsLoading');
        var area = document.getElementById('aiAnswerArea');
        var qLoading = document.getElementById('aiLoading');
        var errorDiv = document.getElementById('aiError');
        var answerDiv = document.getElementById('aiAnswer');
        if (!qContainer || !area) return;

        // 已答按钮列表
        var answeredSet = {};

        // 渲染问题按钮
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

        // 提问
        function askQuestion(question, idx) {
            // 禁用所有按钮
            var btns = qContainer.querySelectorAll('.ai-q-btn');
            btns.forEach(function(b) { b.classList.add('loading'); });

            qLoading.style.display = 'flex';
            errorDiv.style.display = 'none';
            answerDiv.innerHTML = '';
            area.style.display = 'block';

            var fd = new FormData();
            fd.append('id', pid);
            fd.append('type', 'project');
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

        // 页面加载后立即获取 AI 问题
        var qfd = new FormData();
        qfd.append('id', pid);
        qfd.append('type', 'project');

        fetch('/ajax/ai_questions.php', { method: 'POST', body: qfd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data && data.data.questions && data.data.questions.length > 0) {
                renderQuestions(data.data.questions);
            } else {
                // 接口失败时使用默认问题
                renderQuestions([
                    '这个项目怎么赚钱？',
                    '目标客户是谁？',
                    '市场前景如何？',
                    '竞争优势是什么？',
                    '怎么推广营销？',
                    '实施周期要多久？',
                    '售后服务怎么样？',
                    '技术门槛高吗？',
                    '有哪些成功案例？',
                    '投资回报率如何？',
                ]);
            }
        })
        .catch(function() {
            // 网络错误时使用默认问题
            renderQuestions([
                '这个项目怎么赚钱？',
                '目标客户是谁？',
                '市场前景如何？',
                '竞争优势是什么？',
                '怎么推广营销？',
                '实施周期要多久？',
                '售后服务怎么样？',
                '技术门槛高吗？',
                '有哪些成功案例？',
                '投资回报率如何？',
            ]);
        });
    })();
})();
</script>
<?php else: ?>
<!-- ===== 列表模式 ===== -->
<section class="projects-hero">
    <div class="container">
        <h1>项目推荐</h1>
        <p>聚焦互联网前沿，精选优质项目推荐，助您把握商业新机遇</p>
        <div class="projects-stats">
            <div class="stat-item">
                <div class="stat-number"><?= $totalCases ?></div>
                <div class="stat-label">项目推荐</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= $isAgent ? '∞' : $freeLimit ?></div>
                <div class="stat-label">可查看</div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (empty($allCases)): ?>
            <div style="text-align:center;padding:80px 20px;background:#FFF9F0;border-radius:8px;border:1px solid #E8D5A3">
                <h3 style="font-size:22px;margin-bottom:12px;color:#2C1810">项目更新中...</h3>
                <p style="color:#8B7355">我们正在整理更多精彩项目，敬请期待！</p>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($allCases as $index => $p): ?>
                    <?php
                    $isLocked = !$isAgent && $freeLimit > 0 && $index >= $freeLimit;
                    $metricsRaw = trim($p['metrics'] ?? '');
                    ?>
                    <div class="case-card <?= $isLocked ? 'locked' : '' ?>">
                        <a href="?id=<?= $p['id'] ?>" style="text-decoration:none;color:inherit;display:block">
                            <div class="case-image"<?= $p['cover_image'] ? ' style="background-image:url(' . h($p['cover_image']) . ')"' : '' ?>>
                                <span class="case-category"><?= h($p['client_type'] ?: '精选项目') ?></span>
                            </div>
                            <div class="case-body">
                                <h3 class="case-title"><?= h($p['title']) ?></h3>
                                <p class="case-desc"><?= h(truncate($p['description'], 100)) ?></p>
                                <?php if ($metricsRaw): ?>
                                <div class="case-metrics">
                                    <span class="metric-num"><?= h($metricsRaw) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($p['tags']): ?>
                                <div class="case-tags">
                                    <?php foreach (explode(',', $p['tags']) as $tag): ?>
                                    <span class="case-tag"><?= h(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php if ($isLocked): ?>
                        <div class="lock-overlay" onclick="window.location.href='recruitment.php'">
                            <div class="lock-content">
                                <div class="lock-icon"></div>
                                <div class="lock-title">加入解锁全部项目</div>
                                <div class="lock-desc">
                                    目前显示前 <?= $freeLimit ?> 个项目<br>
                                    成为代理商查看全部 <?= $totalCases ?> 个项目
                                </div>
                                <div class="lock-cta">立即加入 →</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$isAgent && $freeLimit > 0 && $totalCases > $freeLimit): ?>
            <div style="text-align:center;margin-top:40px">
                <div style="background:#FFF9F0;border-radius:8px;padding:40px;display:inline-block;border:1px solid #E8D5A3">
                    <h3 style="font-size:20px;margin-bottom:10px;color:#2C1810">还有 <?= $totalCases - $freeLimit ?> 个精选项目</h3>
                    <p style="color:#8B7355;margin-bottom:20px">成为官方授权代理商，解锁全部项目推荐和专属权益</p>
                    <a href="recruitment.php" class="btn btn-primary" style="font-size:15px;padding:12px 36px">立即加入代理商</a>
                    <a href="apply.php" class="btn btn-outline" style="font-size:15px;padding:12px 36px;margin-left:12px">申请入驻</a>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
