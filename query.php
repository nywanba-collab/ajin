<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '授权代理商查询 - ' . getSetting('site_name');
$siteDescription = '通过手机号/微信号/淘宝店铺/域名查询代理商是否为官方授权合作伙伴，保障您的正版权益。';
$pageKeywords = '授权查询,代理商验证,正版授权,授权代理商';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$result = null;
$searched = false;

if ($keyword) {
    $result = searchAgent($keyword);
    $searched = true;
}

// 获取随机查询提示
$db = DB::getInstance();
$hintFields = ['phone', 'wechat', 'taobao_shop', 'domain', 'xianyu_id', 'pdd_shop'];
$privateFields = ['phone', 'wechat', 'xianyu_id', 'pdd_shop'];
$queryHints = [];
$allAgents = $db->fetchAll("SELECT * FROM `agents` WHERE `status` = 1 AND `audit_status` = 1 ORDER BY RAND() LIMIT 10");
foreach ($allAgents as $agent) {
    foreach ($hintFields as $field) {
        if (!empty($agent[$field])) {
            $displayValue = in_array($field, $privateFields) ? getAgentContact($agent, $field) : $agent[$field];
            if (!in_array($displayValue, $queryHints)) {
                $queryHints[] = $displayValue;
            }
        }
    }
}
$queryHints = array_slice($queryHints, 0, 6);

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">授权代理商查询</h1>
        <p class="section-subtitle">输入手机号、微信号、淘宝店铺名、域名、公司名称、闲鱼号或拼多多店铺，验证代理商是否为官方授权</p>

        <!-- 查询表单 -->
        <div class="query-box">
            <form class="query-form" action="" method="GET">
                <input type="text" name="keyword" placeholder="请输入手机号 / 微信号 / 淘宝店 / 域名 / 公司名 / 闲鱼号 / 拼多多店"
                       value="<?= h($keyword) ?>" required>
                <button type="submit">查 询</button>
            </form>
            <div class="query-hints">
                <span>试试：</span>
                <?php foreach ($queryHints as $hint): ?>
                <span class="query-hint-tag"><?= h($hint) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 查询结果 -->
        <?php if ($searched): ?>

            <?php if ($result): ?>
            <!-- 授权代理商家 -->
            <div class="result-card authorized fade-in">
                <div class="result-header">
                    <h2>恭喜！该商家为官方授权代理商</h2>
                    <p>您可以放心购买，享受官方正版保障</p>
                </div>
                <div class="result-body">
                    <table class="result-table">
                        <tr>
                            <td>代理商名称</td>
                            <td><strong><?= h($result['name']) ?></strong></td>
                        </tr>
                        <tr>
                            <td>授权编号</td>
                            <td><?= h($result['cert_number']) ?></td>
                        </tr>
                        <tr>
                            <td>代理等级</td>
                            <td><span class="agent-level <?= agentLevelClass($result['level']) ?>"><?= agentLevelName($result['level']) ?></span></td>
                        </tr>
                        <tr>
                            <td>联系人</td>
                            <td><?= h($result['contact_person']) ?></td>
                        </tr>
                        <tr>
                            <td>手机号</td>
                            <td><?= h(getAgentContact($result, 'phone')) ?></td>
                        </tr>
                        <?php if ($result['wechat']): ?>
                        <tr>
                            <td>微信号</td>
                            <td><?= h(getAgentContact($result, 'wechat')) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($result['taobao_shop']): ?>
                        <tr>
                            <td>淘宝店铺</td>
                            <td><?= h(getAgentContact($result, 'taobao_shop')) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($result['domain']): ?>
                        <tr>
                            <td>授权域名</td>
                            <td><?= h($result['domain']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($result['xianyu_id'])): ?>
                        <tr>
                            <td>闲鱼号</td>
                            <td><?= h($result['xianyu_id']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($result['pdd_shop'])): ?>
                        <tr>
                            <td>拼多多店铺</td>
                            <td><?= h($result['pdd_shop']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td>授权日期</td>
                            <td><?= $result['authorized_at'] ?> 至 <?= $result['expire_at'] ?></td>
                        </tr>
                        <tr>
                            <td>状态</td>
                            <td>
                                <?php if (strtotime($result['expire_at']) >= time()): ?>
                                <span style="color:#15803d;font-weight:600">● 授权有效</span>
                                <?php else: ?>
                                <span style="color:#dc2626;font-weight:600">● 授权已过期</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($result['description']): ?>
                        <tr>
                            <td>备注</td>
                            <td><?= h($result['description']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($result['cert_image']): ?>
                    <div style="margin-top:24px;text-align:center;border-top:1px solid #E8D5A3;padding-top:24px">
                        <p style="font-size:14px;color:#8B7355;margin-bottom:12px">授权证书</p>
                        <img src="<?= SITE_URL ?>/<?= h($result['cert_image']) ?>" alt="授权证书" class="cert-image" loading="lazy">
                    </div>
                    <?php else: ?>
                    <div style="margin-top:24px;text-align:center;border-top:1px solid #E8D5A3;padding-top:24px">
                        <p style="font-size:14px;color:#8B7355">授权证书可在后台管理系统查看</p>
                    </div>
                    <?php endif; ?>

                    <!-- 操作按钮区域 -->
                    <div style="margin-top:24px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                        <a href="agent-detail.php?id=<?= $result['id'] ?>" class="btn btn-accent btn-sm">
                            查看代理商详情
                        </a>
                    </div>

                    <!-- 已授权 → 推荐朋友 -->
                    <div style="margin-top:24px;padding:20px;background:#F5EDD6;border:1px solid #E8D5A3;border-radius:8px;text-align:center">
                        <h3 style="font-size:16px;color:#B8860B;margin-bottom:8px">推荐朋友加入授权代理商</h3>
                        <p style="font-size:13px;color:#5C4A2A;margin-bottom:12px">
                            如果您有朋友也想成为授权代理商，分享给他们吧！
                        </p>
                        <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                            <a href="<?= SITE_URL ?>/apply.php" class="btn btn-success btn-sm">推荐朋友加入</a>
                            <button class="btn btn-primary btn-sm" onclick="copyShareLink()">复制分享链接</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- 未授权 -->
            <div class="result-card unauthorized fade-in">
                <div class="result-header">
                    <h2>未查询到授权信息</h2>
                    <p>该商家不是我们的官方授权代理商</p>
                </div>
                <div class="result-body">
                    <div class="unauthorized-warning">
                        <p><strong>重要提醒：</strong></p>
                        <p>您查询的商家 <strong>"<?= h($keyword) ?>"</strong> 未在官方授权代理商名录中。</p>
                        <p>请务必通过 <strong>官方授权渠道</strong> 购买产品，否则可能面临：</p>
                        <p> 无法激活或使用异常</p>
                        <p> 无法获得官方技术支持和版本更新</p>
                        <p> 存在数据泄露和安全风险</p>
                        <p> 售后无保障，维权困难</p>
                    </div>
                    <div style="text-align:center;margin-top:20px">
                        <p style="color:#5C4A2A;margin-bottom:16px">如您需要购买正版产品，请联系官方渠道：</p>
                        <p style="margin:8px 0">官方电话：<strong><?= h(getSetting('contact_phone')) ?></strong></p>
                        <p style="margin:8px 0">官方邮箱：<strong><?= h(getSetting('contact_email')) ?></strong></p>
                        <br>
                        <a href="<?= SITE_URL ?>/recruitment.php" class="btn btn-accent btn-sm">了解如何成为授权代理商</a>
                    </div>

                    <!-- 未授权 → 招商引导 -->
                    <div style="margin-top:24px;padding:24px;background:#B8860B;border-radius:8px;text-align:center;color:#fff">
                        <h3 style="font-size:20px;margin-bottom:8px;font-weight:700">想成为授权代理商？</h3>
                        <p style="font-size:14px;opacity:0.85;margin-bottom:16px">
                            加入我们，全国 500+ 合作伙伴共同见证，零经验也能快速上手。
                        </p>
                        <a href="<?= SITE_URL ?>/apply.php" class="btn" style="background:#fff;color:#B8860B;padding:12px 36px;font-size:15px;font-weight:700;border-radius:50px">立即申请加盟</a>
                        <p style="margin-top:12px;font-size:12px;opacity:0.65">已有 500+ 合作伙伴加入</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- 查询说明 -->
        <div style="max-width:800px;margin:40px auto;background:#fff;border-radius:8px;padding:36px;box-shadow:0 4px 20px rgba(0,0,0,0.06);border:1px solid #E8D5A3">
            <h3 style="margin-bottom:16px;font-size:18px;font-weight:600;color:#2C1810">关于授权查询</h3>
            <p style="color:#5C4A2A;font-size:14px;line-height:1.8">
                1. 您可以通过 <strong>手机号、微信号、淘宝店铺名、域名、公司名称、闲鱼号、拼多多店铺</strong> 七种方式查询代理商授权状态。<br>
                2. 授权信息以官方数据库为准，实时更新。<br>
                3. 如对查询结果有疑问，请拨打官方客服电话 <strong><?= h(getSetting('contact_phone')) ?></strong> 核实。<br>
                4. 授权代理商名单定期更新，如有遗漏请联系我们补充。<br>
                5. 任何非官方渠道购买的软件产品，本公司概不承担相关责任与技术支持。
            </p>
        </div>
    </div>
</section>

<style>
.agent-level {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.level-normal { background: #f3f4f6; color: #374151; }
.level-senior { background: #dbeafe; color: #1d4ed8; }
.level-gold { background: #fef3c7; color: #92400e; }
.level-strategy { background: #fce7f3; color: #9d174d; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
