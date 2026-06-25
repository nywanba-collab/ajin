<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '申请进度查询 - ' . getSetting('site_name');
$siteDescription = '查询代理商入驻申请的审核进度。输入提交时填写的手机号即可查看当前状态。';
$pageKeywords = '申请进度,入驻查询,代理商审核';

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$result = null;
$searched = false;

if ($phone) {
    $db = DB::getInstance();
    // 统一查询 agents 表（入驻申请和代理商已合并为同一数据源）
    $result = $db->fetchOne("SELECT * FROM `agents` WHERE `phone` = ?", [$phone]);
    if ($result) {
        $statusKey = (int)($result['audit_status'] ?? 0);
    }
    $searched = true;
}

$statusLabels = [0 => '待审核', 1 => '已通过', 2 => '已驳回', 3 => '已签约'];
$statusColors = [0 => '#92400e', 1 => '#15803d', 2 => '#dc2626', 3 => '#1d4ed8'];
$statusBgs = [0 => '#fffbeb', 1 => '#f0fdf4', 2 => '#fef2f2', 3 => '#eff6ff'];
$statusIcons = [0 => '⏳', 1 => '✅', 2 => '❌', 3 => '🤝'];
$statusMessages = [
    0 => '您的申请已收到，我们将在1-3个工作日内完成审核，请耐心等待。',
    1 => '恭喜！您的申请已通过审核。现在您可以使用手机号和密码登录代理商管理中心开始业务了。',
    2 => '很抱歉，您的申请未通过审核。如有疑问请联系我们获取更多信息。',
    3 => '恭喜！您已正式签约成为我们的授权代理商。欢迎加入大家庭！'
];

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:700px">
        <h1 class="section-title">申请进度查询</h1>
        <p class="section-subtitle">输入提交申请时填写的手机号，查看审核状态</p>

        <!-- 查询表单 -->
        <div style="background:#fff;border-radius:12px;padding:32px;box-shadow:var(--shadow-lg);margin-bottom:30px">
            <form method="GET" style="display:flex;gap:12px">
                <input type="text" name="phone" value="<?= h($phone) ?>" placeholder="请输入手机号" required
                    style="flex:1;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;font-size:16px;outline:none;transition:border-color 0.2s"
                    onfocus="this.style.borderColor='#B8860B'" onblur="this.style.borderColor='#e2e8f0'">
                <button type="submit" style="padding:12px 28px;background:linear-gradient(135deg,#B8860B,#D4A843);color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;white-space:nowrap">查询</button>
            </form>
        </div>

        <?php if ($searched): ?>
            <?php if ($result): ?>
                <?php $s = $statusKey; ?>
                <!-- 状态卡片 -->
                <div style="background:<?= $statusBgs[$s] ?>;border:2px solid <?= $statusColors[$s] ?>;border-radius:16px;padding:36px;text-align:center">
                    <div style="font-size:48px;margin-bottom:12px"><?= $statusIcons[$s] ?></div>
                    <div style="font-size:24px;font-weight:700;color:<?= $statusColors[$s] ?>;margin-bottom:8px">
                        <?= $statusLabels[$s] ?>
                    </div>
                    <p style="font-size:15px;color:#475569;line-height:1.6;margin-bottom:24px">
                        <?= $statusMessages[$s] ?>
                    </p>

                    <!-- 申请信息 -->
                    <div style="background:#fff;border-radius:10px;padding:20px;text-align:left;margin-bottom:20px">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <tr>
                                <td style="padding:8px 12px;color:#94a3b8;width:80px">公司</td>
                                <td style="padding:8px 12px;color:#2C1810;font-weight:600"><?= h($result['name']) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px 12px;color:#94a3b8">联系人</td>
                                <td style="padding:8px 12px;color:#2C1810"><?= h($result['contact_person'] ?? '未填写') ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px 12px;color:#94a3b8">手机号</td>
                                <td style="padding:8px 12px;color:#2C1810"><?= h($result['phone']) ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px 12px;color:#94a3b8">城市</td>
                                <td style="padding:8px 12px;color:#2C1810"><?= h($result['city'] ?? '未填写') ?></td>
                            </tr>
                            <?php if ($s == 2 && !empty($result['audit_remark'])): ?>
                            <tr>
                                <td style="padding:8px 12px;color:#94a3b8">驳回原因</td>
                                <td style="padding:8px 12px;color:#dc2626"><?= h($result['audit_remark']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <?php if ($s == 0): ?>
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;text-align:left">
                        <strong>💡 温馨提示：</strong>审核通过后您将收到邮件通知，或再次访问本页面查看最新状态。
                    </div>
                    <?php elseif ($s == 1): ?>
                    <a href="agent/login.php" style="display:inline-block;padding:12px 32px;background:linear-gradient(135deg,#B8860B,#D4A843);color:#fff;text-decoration:none;border-radius:8px;font-size:16px;font-weight:600">登录代理商管理中心 →</a>
                    <?php elseif ($s == 2): ?>
                    <p style="font-size:13px;color:#94a3b8">如需重新申请或咨询，请联系我们：<?= h(getSetting('contact_phone')) ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- 未找到 -->
                <div style="background:#fef2f2;border:2px solid #fecaca;border-radius:16px;padding:36px;text-align:center">
                    <div style="font-size:48px;margin-bottom:12px">🔍</div>
                    <div style="font-size:20px;font-weight:700;color:#dc2626;margin-bottom:8px">未找到申请记录</div>
                    <p style="font-size:14px;color:#64748b;line-height:1.6">
                        没有找到手机号为 <strong><?= h($phone) ?></strong> 的申请记录。<br>
                        请确认手机号输入是否正确，或<a href="apply.php" style="color:#B8860B;font-weight:600">提交新的申请</a>。
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 提示信息 -->
        <div style="margin-top:30px;padding:20px;background:#f8fafc;border-radius:10px;font-size:13px;color:#64748b;line-height:1.8">
            <strong>常见问题：</strong><br>
            • <strong>如何查询？</strong>输入提交申请时填写的手机号即可查看当前审核状态。<br>
            • <strong>审核需要多久？</strong>我们会在1-3个工作日内完成审核。<br>
            • <strong>审核通过后怎么办？</strong>使用手机号和设置的密码登录 <a href="agent/login.php" style="color:#B8860B">代理商管理中心</a>。<br>
            • <strong>还没有申请？</strong><a href="apply.php" style="color:#B8860B">立即提交入驻申请</a>。
        </div>
    </div>
</section>

<style>
.section-title { font-size:28px;font-weight:700;color:#2C1810;text-align:center;margin-bottom:8px }
.section-subtitle { font-size:15px;color:#8B7355;text-align:center;margin-bottom:30px;line-height:1.5 }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
