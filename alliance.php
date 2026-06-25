<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// 检查是否启用（默认启用）
$enabled = getSetting('alliance_enabled');
if ($enabled === '0') {
    header('Location: ' . SITE_URL);
    exit;
}

$pageTitle = (getSetting('alliance_title') ?: '联盟合作') . ' - ' . getSetting('site_name');
$allianceTitle = getSetting('alliance_title') ?: '联盟合作';
$allianceSubtitle = getSetting('alliance_subtitle') ?: '携手共赢，共建软件行业生态圈';
$allianceIntro = getSetting('alliance_intro') ?: '我们诚邀各领域的优秀伙伴加入我们的生态体系，共同开拓万亿级软件市场。无论您是开发者、渠道商、行业从业者，还是电商平台经营者，都能在这里找到适合您的合作方式。';
$siteDescription = $allianceSubtitle;
$pageKeywords = '联盟合作,生态合作,开发者合作,渠道合作,电商合作,创业合作';

// 读取合作类别
$categories = [];
$raw = getSetting('alliance_categories');
if ($raw) {
    $categories = json_decode($raw, true) ?: [];
}
// 默认数据
if (empty($categories)) {
    $categories = [
        ['icon'=>'💻','title'=>'开发者合作','target_audience'=>'软件开发者、技术团队','description'=>'拥有软件开发能力，希望将产品通过我们的渠道进行销售和推广。我们可以为开发者提供成熟的分销体系和客户资源。'],
        ['icon'=>'🔗','title'=>'渠道合作','target_audience'=>'渠道商、代理商、系统集成商','description'=>'拥有客户资源和销售渠道，希望拓展软件产品线。我们提供完整的产品体系、培训支持和售后服务保障。'],
        ['icon'=>'🤖','title'=>'AI与软件从业者','target_audience'=>'AI从业者、软件咨询师、行业顾问','description'=>'在软件或AI领域有专业背景，希望将行业经验转化为商业价值。我们可以共同开发解决方案，共享收益。'],
        ['icon'=>'🛒','title'=>'电商平台合作','target_audience'=>'淘宝店主、拼多多商家、闲鱼卖家','description'=>'在电商平台拥有店铺和运营经验，希望通过软件产品销售增加收入来源。我们提供产品素材和一件代发支持。'],
        ['icon'=>'🚀','title'=>'轻创业合作','target_audience'=>'想赚钱的个人创业者','description'=>'没有技术背景但渴望创业，希望借助成熟的品牌和产品体系快速起步。我们提供零门槛的入门方案和全程指导。'],
        ['icon'=>'📋','title'=>'采购与合作','target_audience'=>'企业采购方、需求方','description'=>'正在寻找软件产品解决方案，希望找到可靠的供应商。我们提供正版授权、定制开发和长期技术支持服务。'],
    ];
}

include __DIR__ . '/includes/header.php';
?>

<style>
/* ========== Hero ========== */
.alliance-hero {
    background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 100%);
    padding: 70px 0;
    text-align: center;
    color: #F5EDD6;
    border-bottom: 3px solid #B8860B;
    position: relative;
    overflow: hidden;
}
.alliance-hero::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle at 30% 50%, rgba(184,134,11,0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 50%, rgba(212,168,67,0.06) 0%, transparent 50%);
}
.alliance-hero .container { position: relative; z-index: 1; }
.alliance-hero h1 { font-size: 36px; font-weight: 700; margin-bottom: 12px; color: #E8D5A3; }
.alliance-hero p { font-size: 16px; color: #C4A882; max-width: 650px; margin: 0 auto; line-height: 1.8; }

/* ========== 类别 ========== */
.section-block { padding: 60px 0; }
.section-block-alt { background: #F5EDD6; }
.section-title {
    text-align: center;
    font-size: 26px;
    font-weight: 700;
    color: #2C1810;
    margin-bottom: 10px;
}
.section-subtitle {
    text-align: center;
    font-size: 15px;
    color: #8B7355;
    margin-bottom: 36px;
    line-height: 1.6;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}
.cat-card {
    background: #FFF9F0;
    border: 1px solid #E8D5A3;
    border-radius: 12px;
    padding: 32px 28px;
    transition: all 0.3s;
    position: relative;
}
.cat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(184,134,11,0.12);
    border-color: #D4A843;
}
.cat-icon {
    width: 56px; height: 56px;
    line-height: 56px;
    text-align: center;
    font-size: 28px;
    background: #F5EDD6;
    border-radius: 12px;
    margin-bottom: 16px;
    border: 1px solid #E8D5A3;
}
.cat-title { font-size: 18px; font-weight: 700; color: #2C1810; margin-bottom: 6px; }
.cat-target {
    font-size: 13px; color: #D4A843; font-weight: 500;
    margin-bottom: 12px; padding: 2px 0;
}
.cat-target::before { content: '🎯 '; }
.cat-desc { font-size: 14px; color: #8B7355; line-height: 1.7; }

/* ========== CTA ========== */
.cta-section {
    background: linear-gradient(135deg, #2C1810 0%, #1A0F0A 100%);
    padding: 60px 0;
    text-align: center;
    border-top: 2px solid #B8860B;
}
.cta-section h2 { font-size: 28px; font-weight: 700; color: #E8D5A3; margin-bottom: 12px; }
.cta-section p { font-size: 15px; color: #C4A882; max-width: 500px; margin: 0 auto 32px; line-height: 1.7; }
.cta-cards {
    display: flex;
    justify-content: center;
    gap: 32px;
    flex-wrap: wrap;
}
.cta-card {
    background: rgba(255,249,240,0.05);
    border: 1px solid rgba(232,213,163,0.2);
    border-radius: 10px;
    padding: 24px 28px;
    min-width: 180px;
    text-align: center;
}
.cta-card .label { font-size: 13px; color: #C4A882; margin-bottom: 6px; }
.cta-card .value { font-size: 18px; font-weight: 600; color: #E8D5A3; }
.cta-card .value a { color: #E8D5A3; text-decoration: none; }
.cta-card .value a:hover { color: #D4A843; }

/* ========== 响应式 ========== */
@media (max-width: 640px) {
    .alliance-hero h1 { font-size: 26px; }
    .alliance-hero { padding: 50px 0 40px; }
    .categories-grid { grid-template-columns: 1fr; }
    .section-block { padding: 40px 0; }
    .cta-cards { flex-direction: column; align-items: center; }
}
</style>

<!-- Hero -->
<section class="alliance-hero">
    <div class="container">
        <h1><?= h($allianceTitle) ?></h1>
        <p><?= h($allianceSubtitle) ?></p>
        <div style="max-width:700px;margin:20px auto 0;font-size:15px;color:#C4A882;line-height:1.8">
            <?= nl2br(h($allianceIntro)) ?>
        </div>
    </div>
</section>

<!-- 合作类别 -->
<?php if (!empty($categories)): ?>
<section class="section-block">
    <div class="container">
        <h2 class="section-title">合作方式</h2>
        <p class="section-subtitle">选择适合您的合作模式，开启共赢之旅</p>
        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <div class="cat-card">
                <div class="cat-icon"><?= h($cat['icon'] ?? '🤝') ?></div>
                <div class="cat-title"><?= h($cat['title'] ?? '') ?></div>
                <?php if (!empty($cat['target_audience'])): ?>
                    <div class="cat-target"><?= h($cat['target_audience']) ?></div>
                <?php endif; ?>
                <div class="cat-desc"><?= nl2br(h($cat['description'] ?? '')) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>加入我们</h2>
        <p>如果您对以上任何合作方式感兴趣，欢迎通过以下方式联系我们，我们将为您提供详细的合作方案。</p>
        <div class="cta-cards">
            <?php $phone = getSetting('alliance_contact_phone') ?: getSetting('contact_phone') ?: '13851734013'; ?>
            <?php if ($phone): ?>
            <div class="cta-card">
                <div class="label">📞 联系电话</div>
                <div class="value"><a href="tel:<?= h($phone) ?>"><?= h($phone) ?></a></div>
            </div>
            <?php endif; ?>
            <?php $email = getSetting('alliance_contact_email') ?: getSetting('contact_email') ?: '875229@qq.com'; ?>
            <?php if ($email): ?>
            <div class="cta-card">
                <div class="label">📧 联系邮箱</div>
                <div class="value"><a href="mailto:<?= h($email) ?>"><?= h($email) ?></a></div>
            </div>
            <?php endif; ?>
            <?php $wechat = getSetting('alliance_contact_wechat') ?: getSetting('contact_wechat') ?: '13851734013'; ?>
            <?php if ($wechat): ?>
            <div class="cta-card">
                <div class="label">💬 联系微信</div>
                <div class="value"><?= h($wechat) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
