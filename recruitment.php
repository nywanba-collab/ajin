<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '招商合作 - ' . getSetting('site_name');
$siteDescription = '全国软件代理商招募中！极具竞争力的分成比例、完善的技术支持和培训体系，诚邀合作伙伴加盟。';
$pageKeywords = 'SaaS招商,代理商招募,合作加盟,授权代理';

$db = DB::getInstance();
$sections = $db->fetchAll(
    "SELECT * FROM `recruitment` WHERE `is_published` = 1 ORDER BY `sort_order` ASC, `created_at` DESC"
);

include __DIR__ . '/includes/header.php';
?>

<!-- 招商 Hero -->
<section class="recruit-hero">
    <div class="container">
        <h1>全国授权代理商招募</h1>
        <p>加入我们，共同开拓万亿级 SaaS 市场<br>诚邀全国各地有资源、有渠道的合作伙伴加盟，共创共赢。</p>
    </div>
</section>

<!-- 紧迫感名额限制条 -->
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

<section class="section" style="padding-top:40px">
    <div class="container">
        <div class="recruit-sections">
            <?php if (!empty($sections)): ?>
                <?php foreach ($sections as $sec): ?>
                <div class="recruit-block fade-in">
                    <h2><?= h($sec['title']) ?></h2>
                    <div class="content">
                        <?= cleanHtml($sec['content']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="recruit-block" style="text-align:center;padding:60px;color:#C4A882">
                    <p>招商信息正在更新中，请稍后再来查看</p>
                </div>
            <?php endif; ?>

            <!-- 数据统计横幅 -->
            <div class="recruit-block fade-in" style="text-align:center">
                <h2>我们的成绩</h2>
                <div class="trust-stats" style="margin-top:20px">
                    <div class="trust-stat"><div class="trust-number">500+</div><div class="trust-label">授权合作伙伴</div></div>
                    <div class="trust-stat"><div class="trust-number">50+</div><div class="trust-label">覆盖城市</div></div>
                    <div class="trust-stat"><div class="trust-number">300%</div><div class="trust-label">平均收益增长</div></div>
                    <div class="trust-stat"><div class="trust-number">99%</div><div class="trust-label">续约率</div></div>
                </div>
            </div>

            <!-- 收益估算器 -->
            <div class="recruit-block fade-in">
                <h2>收益估算</h2>
                <p style="color:#8B7355;margin-bottom:20px">假设每月销售额，看看您的预估收益</p>
                <div style="background:#FFF9F0;border-radius:8px;padding:24px">
                    <div style="margin-bottom:16px">
                        <label style="display:block;font-weight:600;margin-bottom:8px;color:#2C1810">月销售额（万元）</label>
                        <input type="range" id="revenueSlider" min="1" max="100" value="10" style="width:100%">
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:#C4A882">
                            <span>1 万</span>
                            <span id="revenueValue">10 万</span>
                            <span>100 万</span>
                        </div>
                    </div>
                    <div id="revenueResult" style="padding:20px;background:#fff;border-radius:8px;text-align:center">
                        <p style="font-size:14px;color:#C4A882">您的预估月收益</p>
                        <p style="font-size:36px;font-weight:800;color:#B8860B;margin:8px 0">
                            <span id="profitAmount">3.0</span> 万元
                        </p>
                        <p style="font-size:13px;color:#C4A882">按平均 30% 收益比例估算，仅供参考</p>
                    </div>
                </div>
            </div>

            <!-- 联系我们 CTA -->
            <div class="contact-cta fade-in">
                <h2>立即加入我们</h2>
                <p>如果您对我们的代理商计划感兴趣，请立即联系我们！</p>
                <p style="font-size:20px;font-weight:700;margin-bottom:12px"><?= h(getSetting('contact_phone')) ?></p>
                <p style="margin-bottom:20px"><?= h(getSetting('contact_email')) ?></p>
                <a href="index.php#contact" class="btn btn-primary">在线咨询</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
