<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/license-functions.php';

// 授权检查
$__licenseCheck = checkLicenseStatus();
if (!$__licenseCheck['allowed']) {
    $__siteName = getSetting('site_name');
    $__code = $__licenseCheck['code'] ?? '';
    http_response_code(403);
    ?><!DOCTYPE html>
<html lang="zh-CN">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>系统未授权 - <?= h($__siteName) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#fef2f2; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:20px; }
.card { background:#fff; padding:48px; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.1); text-align:center; max-width:480px; width:100%; }
.icon { font-size:64px; margin-bottom:16px; }
h1 { color:#dc2626; font-size:24px; margin-bottom:12px; }
p { color:#64748b; line-height:1.7; font-size:15px; margin-bottom:8px; }
.badge { display:inline-block; background:#fef2f2; color:#dc2626; padding:4px 12px; border-radius:20px; font-size:13px; margin-top:16px; }
</style>
</head>
<body>
<div class="card">
    <div class="icon"><?= $__code === 'NO_LICENSE' ? '🔒' : '🚫' ?></div>
    <h1><?= $__code === 'NO_LICENSE' ? '系统尚未激活' : '系统已被暂停' ?></h1>
    <p><?= h($__licenseCheck['message']) ?></p>
    <p>请联系开发人员 子晓 微信/电话：13851734013</p>
    <p style="margin-top:20px;font-size:13px;color:#94a3b8"><?= h($__siteName) ?></p>
    <div class="badge">Access Restricted</div>
</div>
</body>
</html><?php
    exit;
}
unset($__licenseCheck);

$pageTitle = getSetting('site_name');
$siteDescription = '代理商 - 官方授权代理商查询平台 - 输入手机号/微信号/淘宝店铺名/域名，一键查询代理商是否为官方授权合作伙伴。购买正版软件，享受官方保障。';
$pageKeywords = 'SaaS授权查询,代理商查询,正版软件查询,授权验证';

// 获取最新新闻
$db = DB::getInstance();
$latestNews = $db->fetchAll(
    "SELECT * FROM `news` WHERE `is_published` = 1 ORDER BY `created_at` DESC LIMIT 3"
);

// Hero 轮播数据：5条有封面的新闻 + 5条有封面的项目
$heroItems = [];
$heroNews = $db->fetchAll("SELECT id, title, cover_image, 'news' AS type FROM `news` WHERE `is_published` = 1 AND `cover_image` != '' ORDER BY `created_at` DESC LIMIT 5");
$heroProjects = $db->fetchAll("SELECT id, title, cover_image, 'project' AS type FROM `project_recommendations` WHERE `is_published` = 1 AND `cover_image` != '' ORDER BY `sort_order` ASC, `id` DESC LIMIT 5");
$heroItems = array_merge($heroNews, $heroProjects);
shuffle($heroItems);

// 获取随机查询提示（从所有启用的代理商中随机取字段值）
$hintFields = ['phone', 'wechat', 'taobao_shop', 'domain', 'xianyu_id', 'pdd_shop'];
$privateFields = ['phone', 'wechat', 'xianyu_id', 'pdd_shop'];
$queryHints = [];
$allAgents = $db->fetchAll("SELECT * FROM `agents` WHERE `status` = 1 AND `audit_status` = 1 ORDER BY RAND() LIMIT 10");
foreach ($allAgents as $agent) {
    foreach ($hintFields as $field) {
        $val = in_array($field, $privateFields) ? getAgentContact($agent, $field) : ($agent[$field] ?? '');
        if (!empty($val) && !in_array($val, $queryHints)) {
            $queryHints[] = $val;
        }
    }
}
// 最多取6个
$queryHints = array_slice($queryHints, 0, 6);

include __DIR__ . '/includes/header.php';
?>

<style>
/* ====== 英雄区窗帘收缩动画 ====== */
#curtain-container {
    position: relative;
}

.hero {
    position: relative;
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 50%, #1A0F0A 100%) !important;
    overflow: hidden;
    max-height: 600px;
    transition: max-height 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 40px 0 36px;
}
.hero.collapsed {
    max-height: 56px;
    padding: 0;
}
.hero.collapsed .container {
    opacity: 0;
    pointer-events: none;
}
.hero .container {
    transition: opacity 0.4s ease;
}

/* 跑马灯 — 默认隐藏，折叠后显示 */
.hero-marquee {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    align-items: center;
    overflow: hidden;
    background: transparent;
}
.hero.collapsed .hero-marquee {
    display: flex;
}
.marquee-track {
    white-space: nowrap;
    animation: marquee-scroll 15s linear infinite;
}
.marquee-text {
    display: inline-block;
    font-size: 16px;
    font-weight: 600;
    color: #FFF9F0;
    letter-spacing: 2px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.marquee-link {
    display: inline-block;
    font-size: 16px;
    font-weight: 600;
    color: #FFE484;
    letter-spacing: 2px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.2);
    text-decoration: none;
    transition: color 0.2s ease;
    border-bottom: 1px solid transparent;
}
.marquee-link:hover {
    color: #FFF;
    border-bottom-color: #FFE484;
}
.marquee-divider {
    display: inline-block;
    margin: 0 24px;
    color: rgba(255,255,255,0.4);
    font-size: 12px;
}
@keyframes marquee-scroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

<?php if (!empty($heroItems)): ?>
/* ====== Hero 轮播 ====== */
.hero-carousel {
    display: flex;
    gap: 16px;
    overflow: hidden;
    position: relative;
    width: 100%;
    padding: 8px 0;
}
.hero-carousel-track {
    display: flex;
    gap: 16px;
    animation: heroScroll 28s linear infinite;
}
.hero-carousel-track:hover {
    animation-play-state: paused;
}
@keyframes heroScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.hero-slide {
    flex: 0 0 220px;
    height: 140px;
    border-radius: 12px;
    overflow: hidden;
    position: relative;
    background-size: cover;
    background-position: center;
    text-decoration: none;
    display: block;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
}
.hero-slide:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 8px 30px rgba(0,0,0,0.35);
}
.hero-slide-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 50%);
}
.hero-slide-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px 14px;
    color: #FFF;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
    z-index: 1;
}
.hero-slide-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 1;
}
.hero-slide-badge.news {
    background: rgba(37,99,235,0.85);
    color: #FFF;
}
.hero-slide-badge.project {
    background: rgba(212,168,67,0.9);
    color: #1A0F0A;
}
<?php endif; ?>
.case-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
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
@media (max-width:640px) {
    .case-grid { grid-template-columns:1fr; }
}

/* 手柄 — 在 hero 外部，收缩时不会遮挡跑马灯 */
.hero-handle {
    text-align: center;
    margin-top: -14px;
    position: relative;
    z-index: 10;
}
.hero-handle button,
.hero-handle-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
    height: 28px;
    background: #D4A843;
    border: none;
    border-radius: 0 0 14px 14px;
    color: #2C1810;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    white-space: nowrap;
    padding: 0 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: background 0.3s ease;
}
.hero-handle button:hover,
.hero-handle-label:hover {
    background: #E8D5A3;
}
.hero-handle.open .hero-handle-label {
    letter-spacing: 2px;
}

/* ====== 分区滑动分隔器 ====== */
.split-section {
    padding: 60px 0 70px;
    background: linear-gradient(180deg, #FFFCF5 0%, #FFF9F0 100%);
    overflow: hidden;
}
.split-header {
    text-align: center;
    margin-bottom: 36px;
}
.split-header h2 {
    font-size: 28px;
    font-weight: 800;
    color: #2C1810;
    margin: 0 0 8px;
    letter-spacing: 2px;
}
.split-header h2 span { color: #B8860B; }
.split-header p {
    font-size: 15px;
    color: #C4A882;
    margin: 0;
    letter-spacing: 0.5px;
}
.split-header .header-accent {
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    margin: 12px auto 0;
    border-radius: 2px;
}

/* — 滑动容器 — */
.split-view {
    display: flex;
    align-items: stretch;
    position: relative;
    border-radius: 16px;
    border: 1px solid #E8D5A3;
    background: #FFF;
    box-shadow: 0 8px 40px rgba(44,24,16,0.06);
    overflow: hidden;
    min-height: 520px;
}
.split-panel {
    transition: width 0.55s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    width: calc(50% - 24px);
    display: flex;
    flex-direction: column;
    position: relative;
}
.panel-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 18px 20px 0;
    opacity: 1;
    transition: opacity 0.3s ease 0.2s;
    scrollbar-width: thin;
    scrollbar-color: #E8D5A3 transparent;
    display: flex;
    flex-direction: column;
}
.panel-scroll > .pl-grid {
    flex: 1;
}
.panel-scroll::-webkit-scrollbar { width: 4px; }
.panel-scroll::-webkit-scrollbar-thumb { background: #E8D5A3; border-radius: 2px; }

/* — 面板头部 — */
.split-panel .pl-header {
    margin-bottom: 12px;
    padding: 0 2px;
}
.split-panel .pl-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C1810;
    margin: 0 0 2px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.split-panel .pl-header h3 .pl-icon { font-size: 20px; }
.split-panel .pl-header p {
    font-size: 13px;
    color: #C4A882;
    margin: 0;
    line-height: 1.5;
}

/* — 卡片网格 — */
.pl-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    flex: 1;
    align-content: start;
}
.pl-card {
    display: block;
    text-decoration: none;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.pl-card:hover {
    border-color: #D4A843;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(184,134,11,0.1);
}

/* ====== 项目推荐 - 醒目列表布局（替代两列卡片） ====== */
.pl-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}
.pl-row {
    display: flex;
    align-items: stretch;
    gap: 12px;
    text-decoration: none;
    background: #FFFCF5;
    border: 1px solid #E8D5A3;
    border-radius: 10px;
    padding: 12px 12px 12px 0;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}
.pl-row:hover {
    border-color: #D4A843;
    box-shadow: 0 6px 20px rgba(184,134,11,0.12);
    background: #FFFAF0;
    transform: translateX(2px);
}
/* 左侧彩色条（按项目序号变色） */
.pl-row-bar {
    width: 4px;
    flex-shrink: 0;
    border-radius: 0 3px 3px 0;
}
.pl-row-bar.c0 { background: linear-gradient(180deg, #D4A843, #B8860B); }
.pl-row-bar.c1 { background: linear-gradient(180deg, #C9A84C, #A0842C); }
.pl-row-bar.c2 { background: linear-gradient(180deg, #B89840, #8B6E20); }
.pl-row-bar.c3 { background: linear-gradient(180deg, #A88838, #7A5E18); }
.pl-row-bar.c4 { background: linear-gradient(180deg, #D4A843, #B8860B); opacity: 0.8; }
/* 第一个项目（featured）特殊处理 */
.pl-row:first-child {
    background: linear-gradient(135deg, #FFF8EC 0%, #FFF0D6 100%);
    border-color: #C9A84C;
    padding: 14px 14px 14px 0;
}
.pl-row:first-child .pl-row-bar {
    width: 5px;
}
.pl-row:first-child .plr-title {
    font-size: 14px;
}
/* 项目序号 */
.plr-rank {
    width: 28px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    color: #C4A882;
}
.pl-row:first-child .plr-rank {
    font-size: 16px;
    color: #D4A843;
}
/* 内容区 */
.plr-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 3px;
    padding: 1px 0;
}
/* 顶栏：客户 + 指标 */
.plr-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.plr-client {
    font-size: 11px;
    font-weight: 600;
    color: #8B7355;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.plr-metric {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    white-space: nowrap;
    flex-shrink: 0;
    line-height: 1.4;
}
.plr-metric svg { flex-shrink: 0; }
/* 标题 */
.plr-title {
    font-size: 13px;
    font-weight: 700;
    color: #2C1810;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
/* 描述 */
.plr-desc {
    font-size: 12px;
    color: #8B7355;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
/* 标签 */
.plr-tags {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-top: 1px;
}
.plr-tags span {
    font-size: 10px;
    padding: 1px 7px;
    border-radius: 3px;
    background: #F5EDD6;
    color: #8B6914;
    border: 1px solid rgba(212,168,67,0.1);
}
/* 右侧箭头 */
.plr-arrow {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    color: #C4A882;
    font-size: 20px;
    font-weight: 300;
    transition: all 0.3s ease;
    padding-left: 6px;
}
.pl-row:hover .plr-arrow {
    transform: translateX(4px);
    color: #D4A843;
}
.pl-img {
    aspect-ratio: 16 / 10;
    background: linear-gradient(135deg, #F5EDD6, #E8D5A3);
    background-size: cover;
    background-position: center;
    position: relative;
    padding: 8px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: flex-start;
    gap: 4px;
}
.pl-img-default {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 48px;
    opacity: 0.4;
}
.pl-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    background: rgba(44,24,16,0.7);
    color: #E8D5A3;
    backdrop-filter: blur(4px);
}
.pl-metric {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
}
.pl-hot {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 9px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 3px;
    background: #E74C3C;
    color: #FFF;
    animation: plPulse 1.5s ease infinite;
}
@keyframes plPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.pl-body { padding: 10px 12px 12px; }
.pl-title {
    font-size: 13px;
    font-weight: 700;
    color: #2C1810;
    margin: 0 0 3px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 0;
}
.pl-desc {
    font-size: 12px;
    color: #8B7355;
    line-height: 1.5;
    margin: 0 0 5px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.pl-tags {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.pl-tags span {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 3px;
    background: #F5EDD6;
    color: #8B6914;
}
.pl-meta {
    display: flex;
    gap: 8px;
    font-size: 11px;
    color: #C4A882;
}
.pl-meta span { display: flex; align-items: center; gap: 3px; }
.pl-footer {
    text-align: center;
    margin-top: auto;
    padding: 10px 0 16px;
    border-top: 1px solid #F5EDD6;
}
.pl-footer a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 18px;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.25s;
}
.pl-footer a:hover {
    background: linear-gradient(135deg, #E8D5A3, #D4A843);
    color: #1A0F0A;
    transform: translateY(-1px);
}

/* — 分隔条 — */
.split-divider {
    flex-shrink: 0;
    width: 48px;
    background: linear-gradient(180deg, #2C1810 0%, #1A0F0A 50%, #2C1810 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 5;
    user-select: none;
}
.split-divider::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 1px;
    background: linear-gradient(180deg, transparent 0%, rgba(212,168,67,0.4) 20%, rgba(212,168,67,0.4) 80%, transparent 100%);
}
.split-divider::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 1px;
    background: linear-gradient(180deg, transparent 0%, rgba(212,168,67,0.4) 20%, rgba(212,168,67,0.4) 80%, transparent 100%);
}
.sd-buttons {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.sd-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid rgba(212,168,67,0.3);
    background: rgba(255,249,240,0.06);
    cursor: pointer;
    color: #D4A843;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
}
.sd-btn:hover {
    background: rgba(212,168,67,0.15);
    border-color: #D4A843;
    transform: scale(1.1);
}
.sd-btn:active { transform: scale(0.95); }
.sd-btn .sd-tooltip {
    position: absolute;
    white-space: nowrap;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 4px;
    background: #1A0F0A;
    color: #E8D5A3;
    border: 1px solid rgba(212,168,67,0.2);
    pointer-events: none;
    opacity: 0;
    transition: all 0.25s;
}
.sd-btn.sd-left .sd-tooltip { right: calc(100% + 10px); top: 50%; transform: translateY(-50%); }
.sd-btn.sd-right .sd-tooltip { left: calc(100% + 10px); top: 50%; transform: translateY(-50%); }
.sd-btn:hover .sd-tooltip { opacity: 1; }

/* 分隔条装饰线 */
.sd-line {
    width: 2px;
    flex: 1;
    max-height: 60px;
    background: linear-gradient(180deg, rgba(212,168,67,0.2), rgba(212,168,67,0.6), rgba(212,168,67,0.2));
    border-radius: 1px;
}
.sd-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #D4A843;
    box-shadow: 0 0 8px rgba(212,168,67,0.4);
}

/* — 拖拽滑轨 — */
.split-rail {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 20px;
    padding: 8px 12px;
    user-select: none;
}
.sr-label {
    font-size: 13px;
    font-weight: 600;
    color: #C4A882;
    white-space: nowrap;
    transition: color 0.3s;
    flex-shrink: 0;
}
.sr-label.active { color: #B8860B; }
.sr-track {
    flex: 1;
    height: 20px;
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
}
.sr-track-bg {
    position: absolute;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 2px;
    background: #E8D5A3;
    top: 50%;
    transform: translateY(-50%);
}
.sr-fill {
    position: absolute;
    left: 0;
    height: 4px;
    border-radius: 2px;
    background: linear-gradient(90deg, #D4A843, #B8860B);
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    transition: width 0.15s ease;
}
.sr-thumb {
    position: absolute;
    top: 50%;
    width: 20px;
    height: 20px;
    transform: translate(-50%, -50%);
    z-index: 3;
    cursor: grab;
    transition: left 0.15s ease;
}
.sr-thumb:active { cursor: grabbing; }
.sr-thumb-inner {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4A843, #B8860B);
    box-shadow: 0 1px 6px rgba(184,134,11,0.35), 0 0 0 3px rgba(212,168,67,0.15);
    transition: all 0.2s;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
.sr-thumb:hover .sr-thumb-inner {
    box-shadow: 0 2px 10px rgba(184,134,11,0.5), 0 0 0 4px rgba(212,168,67,0.25);
    transform: translate(-50%, -50%) scale(1.1);
}
.sr-thumb.dragging .sr-thumb-inner {
    transform: translate(-50%, -50%) scale(1.15);
    box-shadow: 0 3px 14px rgba(184,134,11,0.5), 0 0 0 4px rgba(212,168,67,0.3);
}

@media (max-width: 640px) {
    .split-rail { gap: 8px; padding: 6px 4px; margin-top: 14px; }
    .sr-label { font-size: 11px; }
}

@media (max-width: 1024px) {
    .pl-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
    .split-header h2 { font-size: 22px; }
    .split-view { min-height: 400px; flex-direction: column; }
    .split-panel { width: 100% !important; min-height: 0; }
    .split-panel.panel-collapsed { height: 0 !important; overflow: hidden; }
    .split-panel.panel-expanded { height: auto !important; }
    .split-divider {
        width: 100%;
        height: 40px;
        flex-direction: row;
        order: -1;
    }
    .split-divider::before,
    .split-divider::after { display: none; }
    .sd-buttons { flex-direction: row; }
    .sd-line { width: 60px; height: 2px; max-height: none; flex: none; }
    .sd-btn .sd-tooltip { display: none; }
    .pl-grid { grid-template-columns: 1fr 1fr; }
    /* 手机端隐藏拖拽滑轨 */
    .split-rail { display: none !important; }
    .sd-buttons { gap: 8px; margin: 0 auto; }
}
@media (max-width: 480px) {
    .pl-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .hero-marquee .marquee-text,
    .hero-marquee .marquee-link { font-size: 13px; letter-spacing: 1px; }
    .hero-marquee .marquee-divider { margin: 0 14px; font-size: 10px; }
    .marquee-track { animation-duration: 25s; }
    .hero-handle .hero-handle-label { font-size: 11px; min-width: 60px; height: 24px; }
    .hero.collapsed { max-height: 40px !important; }
    .hero { padding: 24px 0 20px; max-height: 400px; }
}
</style>

<!-- Hero 窗帘区 → 初始展开，3秒后缩为顶部条 -->
<div id="curtain-container" style="position:relative">
<section class="hero" id="hero-section">
    <div class="container">
        <?php if (!empty($heroItems)): ?>
        <!-- 精彩内容轮播（展开时显示） -->
        <div style="text-align:center;margin-bottom:12px">
            <span style="font-size:13px;font-weight:600;color:rgba(255,249,240,0.5);letter-spacing:3px;text-transform:uppercase">✨ 精选内容</span>
        </div>
        <div class="hero-carousel">
            <div class="hero-carousel-track">
                <?php
                // 克隆一份实现无缝滚动
                $displayItems = array_merge($heroItems, $heroItems);
                foreach ($displayItems as $item):
                    $link = $item['type'] === 'news' ? SITE_URL . '/news-detail.php?id=' . $item['id'] : SITE_URL . '/projects.php?id=' . $item['id'];
                ?>
                <a href="<?= $link ?>" class="hero-slide" style="background-image:url(<?= h($item['cover_image']) ?>)">
                    <div class="hero-slide-overlay"></div>
                    <span class="hero-slide-badge <?= $item['type'] ?>"><?= $item['type'] === 'news' ? '📰 新闻' : '💼 项目' ?></span>
                    <span class="hero-slide-label"><?= h($item['title']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <h1>正版 SaaS 软件授权查询平台</h1>
        <p>输入手机号、微信号、淘宝店、域名、公司名称、闲鱼号或拼多多店铺，一键查询代理商是否为官方授权合作伙伴。<br>购买正版，享受官方保障。</p>
        <div class="hero-buttons">
            <a href="<?= SITE_URL ?>/query.php" class="btn btn-primary">立即查询</a>
            <a href="<?= SITE_URL ?>/apply.php" class="btn btn-outline">入驻申请</a>
            <a href="<?= SITE_URL ?>/recruitment.php" class="btn btn-outline">成为代理商</a>
        </div>
        <?php endif; ?>
    </div>
    <!-- 折叠后跑马灯通知 -->
    <div class="hero-marquee" id="hero-marquee">
        <div class="marquee-track">
            <?php
            $marqueeItems = [];
            $saved = getSetting('marquee_items');
            if ($saved) {
                $decoded = json_decode($saved, true);
                if (is_array($decoded)) $marqueeItems = $decoded;
            }
            if (empty($marqueeItems)) {
                $marqueeItems = [['text' => '🎉 好软件找到 — 正版 SaaS 软件授权查询平台，欢迎咨询！', 'url' => '']];
            }
            $parts = [];
            foreach ($marqueeItems as $item) {
                $text = h($item['text']);
                if (!empty($item['url'])) {
                    $parts[] = '<a href="' . h($item['url']) . '" class="marquee-link">' . $text . '</a>';
                } else {
                    $parts[] = '<span class="marquee-text">' . $text . '</span>';
                }
            }
            // 循环3遍拼接，保证滚动内容充裕
            $display = implode('<span class="marquee-divider">✦</span>', $parts);
            echo str_repeat($display . '<span class="marquee-divider">✦</span>', 3);
            ?>
        </div>
    </div>
</section>
    <!-- 折叠手柄 — 移到 hero 外部，收起时不会遮挡跑马灯 -->
    <div class="hero-handle" id="hero-handle">
        <button class="hero-handle-label" id="handle-label">3...</button>
    </div>
</div>

<!-- TASK-28b: 紧迫感名额限制条 -->
<?php if (getSetting('show_quota') === 'on'): ?>
<?php $quotaJson = getSetting('quota_region'); if ($quotaJson): $quotas = json_decode($quotaJson, true); if (is_array($quotas) && !empty($quotas)): ?>
<div class="quota-bar">
    <div class="container">
        <span class="quota-text">热门区域名额紧张：</span>
        <?php foreach ($quotas as $region => $status): ?>
        <span class="quota-tag"><?= h($region) ?>：<?= h($status) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; endif; endif; ?>

<!-- ====== 分区滑动分隔器 - 项目推荐 / 新闻动态 ====== -->
<?php
$projects = [];
try {
    $projects = $db->fetchAll("SELECT * FROM `project_recommendations` WHERE `is_published` = 1 ORDER BY `sort_order` ASC, `id` DESC LIMIT 14");
    } catch (Exception $e) { $projects = []; }
$news = [];
try {
    $news = $db->fetchAll("SELECT * FROM `news` WHERE `is_published` = 1 ORDER BY `created_at` DESC LIMIT 12");
} catch (Exception $e) { $news = []; }
$hasProjects = !empty($projects);
$hasNews = !empty($news);
$catMap = ['company' => '公司动态', 'industry' => '行业资讯', 'product' => '产品更新'];
$catIcons = ['company' => '🏢', 'industry' => '📈', 'product' => '🚀'];
?>
<section class="section split-section" id="splitSection">
    <div class="container">
        <div class="split-header">
            <h2>精选内容 <span>· 左右滑动</span></h2>
            <p>行业新闻与项目推荐一键切换，滑动分隔条即可预览双面精彩</p>
            <div class="header-accent"></div>
        </div>

        <!-- 拖拽滑轨顶部控制器 -->
        <div class="split-rail" id="splitRailTop">
            <span class="sr-label sr-l">行业新闻</span>
            <div class="sr-track" id="srTrackTop">
                <div class="sr-track-bg"></div>
                <div class="sr-fill" id="srFillTop"></div>
                <div class="sr-thumb" id="srThumbTop">
                    <div class="sr-thumb-inner"></div>
                </div>
            </div>
            <span class="sr-label sr-r">项目推荐</span>
        </div>

        <div class="split-view" id="splitView">
            <!-- ===== 左面板：新闻动态 ===== -->
            <div class="split-panel" id="panelLeft">
                <div class="panel-scroll">
                    <div class="pl-header">
                        <h3><span class="pl-icon">📰</span> 新闻动态</h3>
                        <p>了解公司最新资讯与行业趋势</p>
                    </div>
                    <?php if ($hasNews): ?>
                    <div class="pl-grid">
                        <?php foreach ($news as $n): ?>
                        <a href="<?= SITE_URL ?>/news-detail.php?id=<?= $n['id'] ?>" class="pl-card">
                            <div class="pl-img"<?= $n['cover_image'] ? ' style="background-image:url(' . h($n['cover_image']) . ')"' : '' ?>
                                 data-default="<?= $catIcons[$n['category']] ?? '📄' ?>">
                                <span class="pl-badge" style="background:rgba(139,105,20,0.75)">
                                    <?= $catIcons[$n['category']] ?? '📄' ?> <?= $catMap[$n['category']] ?? $n['category'] ?>
                                </span>
                                <?php if (date('Y-m-d') == date('Y-m-d', strtotime($n['created_at']))): ?>
                                <span class="pl-hot">NEW</span>
                                <?php endif; ?>
                            </div>
                            <div class="pl-body">
                                <div class="pl-title"><?= h($n['title']) ?></div>
                                <div class="pl-desc"><?= h(truncate(strip_tags($n['summary'] ?: $n['content']), 90)) ?></div>
                                <div class="pl-tags" style="margin-bottom:6px">
                                    <span><?= $catIcons[$n['category']] ?? '📄' ?> <?= $catMap[$n['category']] ?? $n['category'] ?></span>
                                    <span>👁️ <?= $n['view_count'] ?> 次阅读</span>
                                    <span>📅 <?= date('Y-m-d', strtotime($n['created_at'])) ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="pl-footer">
                        <a href="<?= SITE_URL ?>/news.php">查看全部新闻 →</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== 中间分隔条 ===== -->
            <div class="split-divider" id="splitDivider">
                <div class="sd-buttons">
                    <button class="sd-btn sd-left" id="sdLeft" title="滑动到行业新闻">
                        <span class="sd-tooltip">← 滑动到行业新闻</span>
                        ‹
                    </button>
                    <div class="sd-line"></div>
                    <div class="sd-dot"></div>
                    <div class="sd-line"></div>
                    <button class="sd-btn sd-right" id="sdRight" title="滑动到项目推荐">
                        <span class="sd-tooltip">项目推荐 →</span>
                        ›
                    </button>
                </div>
            </div>

            <!-- ===== 右面板：项目推荐 ===== -->
            <div class="split-panel" id="panelRight">
                <div class="panel-scroll">
                    <div class="pl-header">
                        <h3><span class="pl-icon">💼</span> 项目推荐</h3>
                        <p>聚焦前沿，精选优质项目</p>
                    </div>
                    <?php if ($hasProjects): ?>
                    <div class="pl-list">
                        <?php foreach ($projects as $i => $p): ?>
                        <a href="<?= SITE_URL ?>/projects.php?id=<?= $p['id'] ?>" class="pl-row">
                            <div class="pl-row-bar c<?= $i % 5 ?>"></div>
                            <div class="plr-rank"><?= $i === 0 ? '✦' : ($i + 1) ?></div>
                            <div class="plr-body">
                                <div class="plr-header">
                                    <span class="plr-client"><?= h($p['client_name'] ?: $p['client_type'] ?: '精选项目') ?></span>
                                    <?php if ($p['metrics']): ?>
                                    <span class="plr-metric">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        <?= h($p['metrics']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="plr-title"><?= h($p['title']) ?></div>
                                <div class="plr-desc"><?= h(truncate($p['description'], 80)) ?></div>
                                <?php if ($p['tags']): ?>
                                <div class="plr-tags">
                                    <?php foreach (array_slice(explode(',', $p['tags']), 0, 3) as $tag): ?>
                                    <span><?= h(trim($tag)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="plr-arrow">›</div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="pl-footer">
                        <a href="<?= SITE_URL ?>/projects.php">查看全部项目 →</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 拖拽滑轨底部控制器 -->
        <div class="split-rail" id="splitRail">
            <span class="sr-label sr-l">行业新闻</span>
            <div class="sr-track" id="srTrack">
                <div class="sr-track-bg"></div>
                <div class="sr-fill" id="srFill"></div>
                <div class="sr-thumb" id="srThumb">
                    <div class="sr-thumb-inner"></div>
                </div>
            </div>
            <span class="sr-label sr-r">项目推荐</span>
        </div>
    </div>
</section>

<!-- 数据信任区 -->
<section class="section section-alt" style="padding:48px 0">
    <div class="container">
        <div class="trust-stats">
            <div class="trust-stat">
                <div class="trust-number" data-target="500">0</div>
                <div class="trust-label">+ 授权合作伙伴</div>
            </div>
            <div class="trust-stat">
                <div class="trust-number" data-target="98">0</div>
                <div class="trust-label">% 客户满意度</div>
            </div>
            <div class="trust-stat">
                <div class="trust-number" data-target="50">0</div>
                <div class="trust-label">+ 城市覆盖</div>
            </div>
            <div class="trust-stat">
                <div class="trust-number" data-target="300">0</div>
                <div class="trust-label">% 平均收益增长</div>
            </div>
        </div>
    </div>
</section>

<!-- 特色功能 -->
<section class="section section-alt" id="features">
    <div class="container">
        <div class="section-header">
            <h2>为什么选择官方渠道</h2>
            <p>官方授权代理商为您提供最可靠的服务保障</p>
            <div class="section-divider"></div>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <h3>正版保障</h3>
                <p>所有授权代理商均可查询验证，确保您购买的是正版软件产品，享受全部功能。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
                <h3>技术支持</h3>
                <p>通过官方授权渠道购买的用户享有完整的售后技术支持和产品更新服务。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h3>数据安全</h3>
                <p>官方渠道提供安全合规的产品部署方案，保障您的业务数据安全可靠。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <h3>授权可查</h3>
                <p>每一项授权都可追溯查询，授权证书公开透明，杜绝假冒伪劣。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"/>
                        <polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                </div>
                <h3>及时更新</h3>
                <p>官方授权用户可免费获取所有版本更新和功能迭代，持续享用最新产品。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#B8860B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h3>专属服务</h3>
                <p>享受专属客户经理一对一服务，快速响应、专业解决您的所有疑问。</p>
            </div>
        </div>
    </div>
</section>


<!-- 联系我们 -->
<section class="section section-alt" id="contact">
    <div class="container">
        <div class="section-header">
            <h2>联系我们</h2>
            <p>如有任何疑问，请随时与我们取得联系</p>
            <div class="section-divider"></div>
        </div>
        <div class="query-box" style="max-width:700px">
            <h3><?= h(getSetting('contact_phone')) ?></h3>
            <p style="margin:16px 0;color:#8B7355">服务时间：周一至周五 9:00 - 18:00</p>
            <p style="margin:8px 0;color:#5C4A2A">邮箱：<?= h(getSetting('contact_email')) ?></p>
            <p style="margin:8px 0;color:#5C4A2A">地址：<?= h(getSetting('contact_address')) ?></p>
            <br>
            <p style="font-size:14px;color:#C4A882;line-height:1.8">
                温馨提示：请务必通过官方授权渠道购买产品，<br>
                非授权渠道购买的软件无法获得官方技术支持和版本更新，且存在安全隐患。
            </p>
        </div>
    </div>
</section>

<script>
// ====== 英雄区窗帘收缩 + 倒计时 + 悬停展开 ======
(function() {
    var hero = document.getElementById('hero-section');
    var handle = document.getElementById('hero-handle');
    var label = document.getElementById('handle-label');
    var isOpen = true;
    var hoverMode = false;
    var isMobile = window.innerWidth <= 768;
    var countdownSec = isMobile ? 5 : 30;
    var leaveTimer = null;
    var scrolledAway = false;

    // 倒计时后自动折叠
    function startCountdown() {
        countdownSec = isMobile ? 5 : 30;
        label.textContent = countdownSec + 's';
        var timer = setInterval(function() {
            countdownSec--;
            if (countdownSec > 0) {
                label.textContent = countdownSec + 's';
            } else {
                clearInterval(timer);
                doCollapse();
                label.textContent = '点击打开';
            }
        }, 1000);
        handle._timer = timer;
    }

    function doCollapse() {
        hero.classList.add('collapsed');
        handle.classList.add('open');
        isOpen = false;
        hoverMode = false;
        if (leaveTimer) { clearTimeout(leaveTimer); leaveTimer = null; }
    }

    function doExpand() {
        hero.classList.remove('collapsed');
        handle.classList.remove('open');
        isOpen = true;
    }

    // 鼠标离开英雄区 → 0.5秒后折叠（仅 hoverMode 时生效）
    hero.addEventListener('mouseleave', function() {
        if (!hoverMode) return;
        if (leaveTimer) clearTimeout(leaveTimer);
        leaveTimer = setTimeout(function() {
            if (isOpen) {
                doCollapse();
                label.textContent = '点击打开';
            }
        }, 500);
    });
    hero.addEventListener('mouseenter', function() {
        if (leaveTimer) { clearTimeout(leaveTimer); leaveTimer = null; }
    });

    // 初始倒计时
    startCountdown();

    // 手机端：页面滚动即时折叠
    if (isMobile) {
        var scrollThrottle = null;
        window.addEventListener('scroll', function() {
            if (scrolledAway) return;
            if (scrollThrottle) return;
            scrollThrottle = setTimeout(function() {
                scrollThrottle = null;
                if (window.scrollY > 80 && isOpen) {
                    scrolledAway = true;
                    if (handle._timer) { clearInterval(handle._timer); handle._timer = null; }
                    if (leaveTimer) { clearTimeout(leaveTimer); leaveTimer = null; }
                    doCollapse();
                    label.textContent = '点击打开';
                }
            }, 200);
        });
    }

    // 点击手柄切换
    handle.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleHero();
    });

    // 点击缩起的条也能展开
    hero.addEventListener('click', function() {
        if (!isOpen) {
            toggleHero();
        }
    });

    function toggleHero() {
        if (handle._timer) {
            clearInterval(handle._timer);
            handle._timer = null;
        }
        if (leaveTimer) { clearTimeout(leaveTimer); leaveTimer = null; }

        if (isOpen) {
            doCollapse();
            label.textContent = '点击打开';
        } else {
            scrolledAway = false; // 手动展开后重置，允许再次滚动折叠
            doExpand();
            startCountdown();
            hoverMode = true; // 手动展开后启用悬停自动折叠
        }
    }
})();

// ====== 拖拽滑轨分隔器 ======
(function() {
    var panelL = document.getElementById('panelLeft');
    var panelR = document.getElementById('panelRight');
    var btnL = document.getElementById('sdLeft');
    var btnR = document.getElementById('sdRight');
    // 上下两个滑轨
    var rails = [
        { track: document.getElementById('srTrackTop'), thumb: document.getElementById('srThumbTop'), fill: document.getElementById('srFillTop') },
        { track: document.getElementById('srTrack'),   thumb: document.getElementById('srThumb'),   fill: document.getElementById('srFill') }
    ];
    var srLabelL = document.querySelectorAll('.sr-l');
    var srLabelR = document.querySelectorAll('.sr-r');
    var activeTrack = rails[1].track; // 默认用底部
    if (!panelL || !panelR || !rails[0].track || !rails[1].track) return;

    var dividerW = 48;
    var isDragging = false;
    var snapPoints = [0, 50, 100];

    // 更新所有滑轨
    function updateRails(pct) {
        for (var i = 0; i < rails.length; i++) {
            rails[i].fill.style.width = pct + '%';
            rails[i].thumb.style.left = pct + '%';
        }
        for (var j = 0; j < srLabelL.length; j++) {
            srLabelL[j].classList.toggle('active', pct < 50);
            srLabelR[j].classList.toggle('active', pct > 50);
        }
    }

    function setPosition(pct) {
        pct = Math.max(0, Math.min(100, pct));
        var w = activeTrack.offsetWidth || 600;
        var total = 100 - (dividerW / w) * 100;
        var leftPct = (1 - pct / 100) * total;
        var rightPct = (pct / 100) * total;

        var trans = isDragging ? 'none' : 'width 0.45s cubic-bezier(0.4,0,0.2,1)';
        panelL.style.transition = trans;
        panelR.style.transition = trans;
        panelL.style.width = leftPct + '%';
        panelR.style.width = rightPct + '%';

        updateRails(pct);
    }

    function snapToNearest() {
        var pct = parseFloat(activeTrack.querySelector('.sr-thumb').style.left) || 50;
        var nearest = snapPoints.reduce(function(prev, curr) {
            return Math.abs(curr - pct) < Math.abs(prev - pct) ? curr : prev;
        });
        isDragging = false;
        for (var i = 0; i < rails.length; i++)
            rails[i].thumb.classList.remove('dragging');
        setPosition(nearest);
    }

    function posFromEvent(e, track) {
        var rect = track.getBoundingClientRect();
        var clientX = e.touches ? e.touches[0].clientX : e.clientX;
        return Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    }

    function startDrag(e, track) {
        activeTrack = track;
        isDragging = true;
        for (var i = 0; i < rails.length; i++)
            rails[i].thumb.classList.add('dragging');
        var pct = posFromEvent(e, track);
        setPosition(pct);
        e.preventDefault();
    }

    function moveDrag(e) {
        if (!isDragging) return;
        var pct = posFromEvent(e, activeTrack);
        setPosition(pct);
        e.preventDefault();
    }

    function endDrag(e) {
        if (!isDragging) return;
        snapToNearest();
    }

    // 为每条轨道绑定事件
    function bindTrack(track) {
        // 点击轨道跳转
        track.addEventListener('mousedown', function(e) {
            if (e.target.closest('.sr-thumb')) return;
            activeTrack = track;
            var pct = posFromEvent(e, track);
            var nearest = snapPoints.reduce(function(prev, curr) {
                return Math.abs(curr - pct) < Math.abs(prev - pct) ? curr : prev;
            });
            setPosition(nearest);
        });
        // 拖拽
        track.addEventListener('mousedown', function(e) {
            if (e.target.closest('.sr-thumb')) startDrag(e, track);
        });
        track.addEventListener('touchstart', function(e) {
            startDrag(e, track);
        }, { passive: false });
    }
    bindTrack(rails[0].track);
    bindTrack(rails[1].track);

    // 全局拖拽监听
    document.addEventListener('mousemove', moveDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', moveDrag, { passive: false });
    document.addEventListener('touchend', endDrag);

    // 左右箭头按钮
    btnL.addEventListener('click', function() {
        var cur = parseFloat(activeTrack.querySelector('.sr-thumb').style.left) || 50;
        if (cur > 0) setPosition(0);
        else setPosition(50);
    });
    btnR.addEventListener('click', function() {
        var cur = parseFloat(activeTrack.querySelector('.sr-thumb').style.left) || 50;
        if (cur < 100) setPosition(100);
        else setPosition(50);
    });

    panelL.addEventListener('click', function(e) {
        if (e.target.closest('a') || e.target.closest('.pl-footer')) return;
        setPosition(0);
    });
    panelR.addEventListener('click', function(e) {
        if (e.target.closest('a') || e.target.closest('.pl-footer')) return;
        setPosition(100);
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) setPosition(50);
    });

    setPosition(50);
})();

// ====== 图片加载失败回退 ======
(function() {
    function checkImages() {
        var imgs = document.querySelectorAll('.pl-img');
        imgs.forEach(function(div) {
            var bg = div.style.backgroundImage;
            if (!bg) {
                var def = div.getAttribute('data-default') || '📄';
                div.innerHTML = '<div class="pl-img-default">' + def + '</div>' + div.innerHTML;
                return;
            }
            var url = bg.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
            var img = new Image();
            img.onload = function() {};
            img.onerror = function() {
                var def = div.getAttribute('data-default') || '📄';
                div.style.backgroundImage = 'none';
                div.innerHTML = '<div class="pl-img-default">' + def + '</div>' + div.innerHTML;
            };
            img.src = url;
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkImages);
    } else {
        checkImages();
    }
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
