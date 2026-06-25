<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '新闻动态 - ' . getSetting('site_name');
$siteDescription = '了解公司最新资讯、产品更新与行业趋势，获取第一手官方信息。';
$db = DB::getInstance();

// 分类筛选
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * PAGE_SIZE;

$where = "WHERE `is_published` = 1";
$params = [];
if ($category && in_array($category, ['company', 'industry', 'product'])) {
    $where .= " AND `category` = ?";
    $params[] = $category;
}

// 总数
$countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM `news` {$where}", $params);
$total = $countRow['total'];
$totalPages = max(1, ceil($total / PAGE_SIZE));

// 列表（首页取多一条用来做特色文章）
$limit = PAGE_SIZE + 1;
$newsList = $db->fetchAll(
    "SELECT * FROM `news` {$where} ORDER BY `created_at` DESC LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

$catMap = ['company' => '公司动态', 'industry' => '行业资讯', 'product' => '产品更新'];
$catIcons = ['company' => '🏢', 'industry' => '📊', 'product' => '🚀'];

// 分离特色文章和普通文章
$featured = null;
$regularList = $newsList;
if ($page === 1 && empty($category) && !empty($newsList)) {
    $featured = array_shift($regularList);
}

// 获取热门文章
$popularNews = [];
try {
    $popularNews = $db->fetchAll(
        "SELECT `id`, `title`, `view_count`, `created_at` FROM `news` WHERE `is_published` = 1 ORDER BY `view_count` DESC LIMIT 5"
    );
} catch (Exception $e) {}

include __DIR__ . '/includes/header.php';
?>

<style>
/* ====== 新闻页专用样式 ====== */

/* — 英雄区 — */
.news-hero {
    position: relative;
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 40%, #3C2418 100%);
    padding: 72px 0 64px;
    overflow: hidden;
    border-bottom: 3px solid rgba(212,168,67,0.3);
}
.news-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 20%, rgba(212,168,67,0.06) 0%, transparent 70%),
        radial-gradient(ellipse 60% 80% at 80% 80%, rgba(232,213,163,0.04) 0%, transparent 70%);
    pointer-events: none;
}
.news-hero::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(212,168,67,0.3), transparent);
}
.news-hero .container {
    position: relative;
    z-index: 1;
}
.news-hero h1 {
    font-size: 36px;
    font-weight: 700;
    color: #E8D5A3;
    margin-bottom: 10px;
    letter-spacing: 1px;
}
.news-hero p {
    font-size: 15px;
    color: rgba(196,168,130,0.8);
    max-width: 520px;
    line-height: 1.7;
    margin-bottom: 0;
}
.news-hero .breadcrumb {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 20px;
    font-size: 13px;
    color: rgba(196,168,130,0.6);
}
.news-hero .breadcrumb a {
    color: rgba(212,168,67,0.7);
    text-decoration: none;
    transition: color 0.2s;
}
.news-hero .breadcrumb a:hover { color: #D4A843; }
.news-hero .breadcrumb span { color: rgba(196,168,130,0.4); }

/* — 分类标签 — */
.news-categories {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 36px;
}
.news-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.25s ease;
    border: 1.5px solid #E8D5A3;
    color: #8B7355;
    background: #FFF9F0;
}
.news-cat-pill:hover {
    border-color: #D4A843;
    color: #2C1810;
    background: #F5EDD6;
    transform: translateY(-1px);
}
.news-cat-pill.active,
.news-cat-pill.active:hover {
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border-color: #B8860B;
    box-shadow: 0 4px 14px rgba(184,134,11,0.25);
    transform: none;
}
.news-cat-pill .pill-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(184,134,11,0.1);
    color: #8B6914;
}
.news-cat-pill.active .pill-count {
    background: rgba(255,249,240,0.2);
    color: #FFF9F0;
}

/* — 特色文章（首条大卡片） — */
.featured-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 16px;
    overflow: hidden;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    margin-bottom: 36px;
    text-decoration: none;
    color: inherit;
    transition: all 0.35s ease;
    box-shadow: 0 2px 12px rgba(184,134,11,0.06);
}
.featured-card:hover {
    border-color: #D4A843;
    box-shadow: 0 12px 40px rgba(184,134,11,0.12);
    transform: translateY(-3px);
}
.featured-card-image {
    position: relative;
    min-height: 280px;
    background: linear-gradient(135deg, #2C1810, #1A0F0A);
    overflow: hidden;
}
.featured-card-image .img-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transition: transform 0.6s ease;
}
.featured-card:hover .img-bg { transform: scale(1.05); }
.featured-card-image .img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,15,10,0.7) 0%, transparent 50%);
}
.featured-card-image .featured-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 14px;
    border-radius: 50px;
    background: rgba(212,168,67,0.9);
    color: #1A0F0A;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    backdrop-filter: blur(4px);
}
.featured-card-body {
    padding: 36px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.featured-card-body .news-cat-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 14px;
    width: fit-content;
}
.featured-card-body .news-cat-tag.company { background: #F5EDD6; color: #8B6914; }
.featured-card-body .news-cat-tag.industry { background: #F5EDD6; color: #8B7355; }
.featured-card-body .news-cat-tag.product { background: #F5EDD6; color: #7C6B2B; }
.featured-card-body h2 {
    font-size: 22px;
    font-weight: 700;
    color: #2C1810;
    margin-bottom: 12px;
    line-height: 1.4;
}
.featured-card-body p {
    font-size: 14px;
    color: #8B7355;
    line-height: 1.8;
    margin-bottom: 18px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.featured-card-meta {
    display: flex;
    gap: 18px;
    font-size: 13px;
    color: #C4A882;
}
.featured-card-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}
.featured-card-meta .read-more {
    margin-left: auto;
    color: #B8860B;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: gap 0.2s;
}
.featured-card:hover .read-more { gap: 8px; }

/* — 文章网格 — */
.news-grid-modern {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 32px;
}
.news-card-modern {
    display: flex;
    flex-direction: column;
    border-radius: 12px;
    overflow: hidden;
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    box-shadow: 0 1px 6px rgba(184,134,11,0.04);
}
.news-card-modern:hover {
    border-color: #D4A843;
    box-shadow: 0 8px 28px rgba(184,134,11,0.1);
    transform: translateY(-4px);
}
.news-card-modern .card-img {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #2C1810, #1A0F0A);
    overflow: hidden;
    flex-shrink: 0;
}
.news-card-modern .card-img .img-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transition: transform 0.5s ease;
}
.news-card-modern:hover .img-bg { transform: scale(1.08); }
.news-card-modern .card-img .img-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,15,10,0.4) 0%, transparent 40%);
}
.news-card-modern .card-img .cat-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.news-card-modern .card-img .cat-badge.company { background: #F5EDD6; color: #8B6914; }
.news-card-modern .card-img .cat-badge.industry { background: #F5EDD6; color: #8B7355; }
.news-card-modern .card-img .cat-badge.product { background: #F5EDD6; color: #7C6B2B; }
.news-card-modern .card-body {
    padding: 18px 20px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.news-card-modern .card-body h3 {
    font-size: 15px;
    font-weight: 600;
    color: #2C1810;
    margin-bottom: 8px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.news-card-modern .card-body p {
    font-size: 13px;
    color: #8B7355;
    line-height: 1.7;
    margin-bottom: 14px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}
.news-card-modern .card-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #C4A882;
    padding-top: 12px;
    border-top: 1px solid #F5EDD6;
}
.news-card-modern .card-meta span {
    display: flex;
    align-items: center;
    gap: 3px;
}

/* — 热门文章侧栏 — */
.news-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 32px;
    align-items: start;
}
.news-main-col { min-width: 0; }
.news-side-col { position: sticky; top: 88px; }

.popular-section {
    background: #FFF9F0;
    border-radius: 12px;
    border: 1px solid #E8D5A3;
    overflow: hidden;
}
.popular-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #1A0F0A, #2C1810);
    border-bottom: 2px solid #D4A843;
}
.popular-header h3 {
    font-size: 15px;
    font-weight: 700;
    color: #E8D5A3;
    display: flex;
    align-items: center;
    gap: 8px;
}
.popular-list { padding: 8px; }
.popular-item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.popular-item:hover { background: #F5EDD6; }
.popular-item .rank {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
    background: #F5EDD6;
    color: #8B7355;
}
.popular-item:nth-child(1) .rank { background: linear-gradient(135deg, #D4A843, #B8860B); color: #FFF9F0; }
.popular-item:nth-child(2) .rank { background: linear-gradient(135deg, #C4A882, #8B7355); color: #FFF9F0; }
.popular-item:nth-child(3) .rank { background: linear-gradient(135deg, #B8860B, #8B6914); color: #FFF9F0; }
.popular-item .popular-info { min-width: 0; }
.popular-item .popular-info h4 {
    font-size: 13px;
    font-weight: 500;
    color: #2C1810;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.5;
    margin-bottom: 4px;
}
.popular-item .popular-info .popular-meta {
    font-size: 11px;
    color: #C4A882;
}

/* — 分页 — */
.pagination-modern {
    display: flex;
    gap: 6px;
    justify-content: center;
    margin: 40px 0 12px;
}
.pagination-modern a,
.pagination-modern span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid #E8D5A3;
    color: #8B7355;
    background: #FFF9F0;
}
.pagination-modern a:hover {
    border-color: #D4A843;
    color: #2C1810;
    background: #F5EDD6;
}
.pagination-modern .active {
    background: linear-gradient(135deg, #D4A843, #B8860B);
    color: #FFF9F0;
    border-color: #B8860B;
    box-shadow: 0 4px 12px rgba(184,134,11,0.2);
}
.pagination-modern .disabled {
    opacity: 0.4;
    cursor: default;
    pointer-events: none;
}
.pagination-modern .page-dots {
    border: none;
    background: none;
    color: #C4A882;
    min-width: 24px;
}

/* — 空状态 — */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #FFF9F0;
    border-radius: 16px;
    border: 1px solid #E8D5A3;
}
.empty-state .empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}
.empty-state h3 {
    font-size: 18px;
    color: #2C1810;
    margin-bottom: 8px;
}
.empty-state p { color: #C4A882; font-size: 14px; }

/* — 响应式 — */
@media (max-width: 900px) {
    .news-layout { grid-template-columns: 1fr; }
    .news-side-col { position: static; }
    .popular-section { margin-top: 24px; }
    .featured-card { grid-template-columns: 1fr; }
    .featured-card-image { min-height: 200px; }
    .featured-card-body { padding: 24px; }
    .featured-card-body h2 { font-size: 18px; }
}
@media (max-width: 768px) {
    .news-hero { padding: 48px 0 40px; }
    .news-hero h1 { font-size: 26px; }
    .news-grid-modern { grid-template-columns: 1fr; }
    .news-hero .breadcrumb { display: none; }
    .pagination-modern a,
    .pagination-modern span { min-width: 36px; height: 36px; font-size: 13px; }
}
@media (min-width: 769px) and (max-width: 1024px) {
    .news-grid-modern { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- ====== 英雄区 ====== -->
<section class="news-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= SITE_URL ?>/index.php">首页</a>
            <span>›</span>
            <span>新闻动态</span>
        </div>
        <h1>新闻动态</h1>
        <p>了解公司最新资讯、产品更新与行业趋势，获取第一手官方信息。</p>
    </div>
</section>

<!-- ====== 内容区 ====== -->
<section class="section" style="padding-top:40px">
    <div class="container">
        <!-- 分类标签 -->
        <div class="news-categories">
            <?php
            $catCounts = [];
            try {
                $counts = $db->fetchAll("SELECT `category`, COUNT(*) AS cnt FROM `news` WHERE `is_published` = 1 GROUP BY `category`");
                foreach ($counts as $c) $catCounts[$c['category']] = $c['cnt'];
            } catch (Exception $e) {}
            $allCount = array_sum($catCounts);
            ?>
            <a href="news.php" class="news-cat-pill <?= empty($category) ? 'active' : '' ?>">
                全部
                <span class="pill-count"><?= $allCount ?></span>
            </a>
            <?php foreach ($catMap as $key => $label):
                $cnt = $catCounts[$key] ?? 0;
            ?>
            <a href="?category=<?= $key ?>" class="news-cat-pill <?= $category === $key ? 'active' : '' ?>">
                <?= $catIcons[$key] ?? '' ?> <?= $label ?>
                <span class="pill-count"><?= $cnt ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($newsList) || $featured): ?>
        <div class="news-layout">
            <div class="news-main-col">
                <?php if ($featured): ?>
                <!-- 特色文章 -->
                <a href="news-detail.php?id=<?= $featured['id'] ?>" class="featured-card">
                    <div class="featured-card-image">
                        <div class="img-bg" style="<?= $featured['cover_image'] ? 'background-image:url(' . h($featured['cover_image']) . ')' : 'background:linear-gradient(135deg,#2C1810,#1A0F0A)' ?>"></div>
                        <div class="img-overlay"></div>
                        <div class="featured-badge">✦ 最新</div>
                    </div>
                    <div class="featured-card-body">
                        <span class="news-cat-tag <?= h($featured['category']) ?>">
                            <?= $catIcons[$featured['category']] ?? '' ?> <?= h($catMap[$featured['category']] ?? $featured['category']) ?>
                        </span>
                        <h2><?= h($featured['title']) ?></h2>
                        <p><?= h(truncate(strip_tags($featured['summary'] ?: $featured['content']), 180)) ?></p>
                        <div class="featured-card-meta">
                            <span>📅 <?= date('Y-m-d', strtotime($featured['created_at'])) ?></span>
                            <span>👁️ <?= $featured['view_count'] ?> 次阅读</span>
                            <span class="read-more">阅读全文 →</span>
                        </div>
                    </div>
                </a>
                <?php endif; ?>

                <!-- 文章网格 -->
                <?php if (!empty($regularList)): ?>
                <div class="news-grid-modern">
                    <?php foreach ($regularList as $news): ?>
                    <a href="news-detail.php?id=<?= $news['id'] ?>" class="news-card-modern">
                        <div class="card-img">
                            <div class="img-bg" style="<?= $news['cover_image'] ? 'background-image:url(' . h($news['cover_image']) . ')' : 'background:linear-gradient(135deg,#2C1810,#1A0F0A)' ?>"></div>
                            <div class="img-overlay"></div>
                            <span class="cat-badge <?= h($news['category']) ?>">
                                <?= h($catMap[$news['category']] ?? $news['category']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h3><?= h($news['title']) ?></h3>
                            <p><?= h(truncate(strip_tags($news['summary'] ?: $news['content']), 100)) ?></p>
                            <div class="card-meta">
                                <span>📅 <?= date('m-d', strtotime($news['created_at'])) ?></span>
                                <span>👁️ <?= $news['view_count'] ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 分页 -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-modern">
                    <?php
                    $pageUrl = 'news.php' . ($category ? '?category=' . $category . '&page=' : '?page=');
                    if ($page > 1):
                    ?>
                    <a href="<?= $pageUrl . ($page - 1) ?>">‹ 上一页</a>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end = min($totalPages, $page + $range);
                    if ($start > 1) echo '<a href="' . $pageUrl . '1">1</a>';
                    if ($start > 2) echo '<span class="page-dots">···</span>';
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <span class="<?= $i === $page ? 'active' : '' ?>"><?php if ($i === $page): ?><?= $i ?><?php else: ?><a href="<?= $pageUrl . $i ?>"><?= $i ?></a><?php endif; ?></span>
                    <?php endfor; ?>
                    <?php if ($end < $totalPages - 1) echo '<span class="page-dots">···</span>'; ?>
                    <?php if ($end < $totalPages): ?>
                    <a href="<?= $pageUrl . $totalPages ?>"><?= $totalPages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="<?= $pageUrl . ($page + 1) ?>">下一页 ›</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="news-side-col">
                <!-- 热门文章 -->
                <?php if (!empty($popularNews)): ?>
                <div class="popular-section">
                    <div class="popular-header">
                        <h3>🔥 热门文章</h3>
                    </div>
                    <div class="popular-list">
                        <?php foreach ($popularNews as $i => $item): ?>
                        <a href="news-detail.php?id=<?= $item['id'] ?>" class="popular-item">
                            <span class="rank"><?= $i + 1 ?></span>
                            <div class="popular-info">
                                <h4><?= h($item['title']) ?></h4>
                                <div class="popular-meta">👁️ <?= $item['view_count'] ?> 次阅读</div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 二维码卡片 -->
                <div style="margin-top:20px;background:linear-gradient(135deg,#1A0F0A,#2C1810);border-radius:12px;padding:24px 20px;text-align:center;border:1px solid rgba(212,168,67,0.2)">
                    <p style="color:#E8D5A3;font-size:13px;font-weight:600;margin-bottom:12px;letter-spacing:1px">扫码关注公众号</p>
                    <?php $qrMp = getSetting('contact_qr_mp'); if ($qrMp): ?>
                    <img src="<?= SITE_URL . '/' . $qrMp ?>" alt="公众号" style="width:140px;height:140px;border-radius:8px;border:2px solid rgba(212,168,67,0.3);display:block;margin:0 auto;">
                    <?php else: ?>
                    <div style="width:140px;height:140px;background:rgba(255,255,255,0.05);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto;border:1px dashed rgba(212,168,67,0.2)">
                        <span style="color:rgba(196,168,130,0.4);font-size:12px">暂无二维码</span>
                    </div>
                    <?php endif; ?>
                    <p style="color:rgba(196,168,130,0.6);font-size:12px;margin-top:10px">获取最新资讯与优惠</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- 空状态 -->
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>暂无新闻内容</h3>
            <p>当前分类下没有任何文章，请查看其他分类。</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
