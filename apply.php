<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '代理商入驻申请 - ' . getSetting('site_name');
$siteDescription = '申请成为软件代理商，加入我们共同开拓万亿级软件市场。提交入驻申请，审核通过后即可开展业务。';
$pageKeywords = '代理商加盟,SaaS代理,授权代理商,加盟申请';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'           => trim($_POST['name'] ?? ''),
        'contact_person' => trim($_POST['contact_person'] ?? ''),
        'phone'          => trim($_POST['phone'] ?? ''),
        'wechat'         => trim($_POST['wechat'] ?? ''),
        'taobao_shop'    => trim($_POST['taobao_shop'] ?? ''),
        'domain'         => trim($_POST['domain'] ?? ''),
        'xianyu_id'      => trim($_POST['xianyu_id'] ?? ''),
        'pdd_shop'       => trim($_POST['pdd_shop'] ?? ''),
        'city'           => trim($_POST['city'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'password'       => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
    ];

    if (empty($data['name']) || empty($data['phone'])) {
        $msg = '请填写公司名称和联系电话';
        $msgType = 'danger';
    } elseif (strlen($data['phone']) < 6) {
        $msg = '请输入有效的联系电话';
        $msgType = 'danger';
    } elseif (empty($data['password'])) {
        $msg = '请设置登录密码';
        $msgType = 'danger';
    } elseif (strlen($data['password']) < 6) {
        $msg = '密码至少需要6位';
        $msgType = 'danger';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $msg = '两次密码输入不一致';
        $msgType = 'danger';
    } else {
        try {
            $db = DB::getInstance();
            $pwdHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // 生成唯一证书编号（避免 cert_number UNIQUE 索引冲突）
            $certNumber = 'APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // domain 为空时用 NULL（MySQL UNIQUE 索引允许多个 NULL，但不允许多个 ''）
            $domain = $data['domain'] ?: null;

            $db->execute(
                "INSERT INTO `agents` (`name`, `contact_person`, `phone`, `wechat`, `taobao_shop`, `domain`, `xianyu_id`, `pdd_shop`, `city`, `address`, `description`, `password`, `cert_number`, `status`, `audit_status`, `password_changed`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 1)",
                [
                    $data['name'], $data['contact_person'], $data['phone'], $data['wechat'],
                    $data['taobao_shop'], $domain, $data['xianyu_id'], $data['pdd_shop'],
                    $data['city'], $data['address'], $data['description'], $pwdHash, $certNumber
                ]
            );
            $msg = '提交成功！您的入驻申请已提交，我们将在1-3个工作日内完成审核。<br><br>
                    您可以使用手机号 <strong>' . h($data['phone']) . '</strong> 和您设置的密码登录 <a href="agent/login.php" style="color:#fff;text-decoration:underline">代理商管理中心</a> 查看申请进度。<br><br>
                    <a href="apply-status.php" style="display:inline-block;margin-top:8px;padding:8px 20px;background:#fff;color:#15803d;text-decoration:none;border-radius:6px;font-weight:600">查询申请状态 →</a>';
            $msgType = 'success';
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'unique') !== false) {
                $msg = '该手机号已提交过申请，请直接登录或联系客服。<br><br>
                        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
                            <a href="agent/login.php" style="display:inline-block;padding:8px 20px;background:#fff;color:#15803d;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;border:1px solid #15803d">🔑 去登录</a>
                            <a href="https://wpa.qq.com/msgrd?v=3&uin=' . h(getSetting('contact_qq')) . '&site=qq&menu=yes" target="_blank" style="display:inline-block;padding:8px 20px;background:#fff;color:#dc2626;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;border:1px solid #dc2626">📞 联系客服</a>
                        </div>';
            } else {
                writeLog(0, 'apply_error', $e->getMessage());
                $msg = '提交失败，请稍后重试。';
            }
            $msgType = 'danger';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:800px">
        <h1 class="section-title">代理商入驻申请</h1>
        <p class="section-subtitle">填写以下信息，提交授权代理商入驻申请</p>

        <?php if ($msg): ?>
        <div class="result-card <?= $msgType === 'success' ? 'authorized' : 'unauthorized' ?>" style="margin-bottom:30px">
            <div class="result-header" style="padding:20px">
                <div class="result-icon" style="font-size:24px;font-weight:700;color:<?= $msgType === 'success' ? '#15803d' : '#dc2626' ?>"><?= $msgType === 'success' ? '✓' : '✗' ?></div>
                <h2 style="font-size:20px"><?= $msgType === 'success' ? '申请已提交' : '提交失败' ?></h2>
                <p><?= $msg ?></p>
            </div>
        </div>
        <?php if ($msgType === 'success'): ?>
        <div style="margin-top:24px;padding:24px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px">
            <h3 style="font-size:16px;margin-bottom:12px;color:#15803d">下一步做什么？</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;color:#475569">
                <div style="padding:12px;background:#fff;border-radius:8px">
                    <strong>查询申请状态</strong>
                    <p style="margin-top:4px;color:#94a3b8;font-size:13px">随时通过手机号查询审核进度：<a href="apply-status.php" target="_blank">查询申请状态 →</a></p>
                </div>
                <div style="padding:12px;background:#fff;border-radius:8px">
                    <strong>登录管理中心</strong>
                    <p style="margin-top:4px;color:#94a3b8;font-size:13px">审核通过后，使用手机号和设置的密码登录 <a href="agent/login.php" target="_blank">代理商管理中心</a></p>
                </div>
                <div style="padding:12px;background:#fff;border-radius:8px">
                    <strong>关注通知</strong>
                    <p style="margin-top:4px;color:#94a3b8;font-size:13px">审核结果将发送至您的邮箱，也可以通过登录管理中心查看</p>
                </div>
                <div style="padding:12px;background:#fff;border-radius:8px">
                    <strong>联系我们</strong>
                    <p style="margin-top:4px;color:#94a3b8;font-size:13px">如有疑问，请拨打 <?= h(getSetting('contact_phone')) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div style="background:#fff;border-radius:12px;padding:40px;box-shadow:var(--shadow-lg)">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_name" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">公司名称 *</label>
                        <input type="text" id="field_name" name="name" required style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_contact_person" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">联系人</label>
                        <input type="text" id="field_contact_person" name="contact_person" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_phone" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">联系电话 *</label>
                        <input type="text" id="field_phone" name="phone" required placeholder="客户可通过此号码查询您的授权" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                        <div style="font-size:12px;color:#94a3b8;margin-top:4px">提交后将作为客户查询您的授权状态的凭证</div>
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_wechat" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">微信号</label>
                        <input type="text" id="field_wechat" name="wechat" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_taobao_shop" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">淘宝店铺名</label>
                        <input type="text" id="field_taobao_shop" name="taobao_shop" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_domain" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">授权域名</label>
                        <input type="text" id="field_domain" name="domain" placeholder="如 agent.yourcompany.com" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_xianyu_id" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">闲鱼号</label>
                        <input type="text" id="field_xianyu_id" name="xianyu_id" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_pdd_shop" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">拼多多店铺名</label>
                        <input type="text" id="field_pdd_shop" name="pdd_shop" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_city" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">所在城市</label>
                        <input type="text" id="field_city" name="city" placeholder="如：南京、上海" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                    <div class="form-group" style="margin-bottom:20px">
                        <label for="field_address" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">详细地址</label>
                        <input type="text" id="field_address" name="address" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:24px">
                    <label for="field_description" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">公司简介 / 业务介绍</label>
                    <textarea name="description" id="field_description" rows="4" placeholder="简单介绍您的公司和服务范围" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box;resize:vertical;min-height:100px"></textarea>
                </div>

                <div class="form-row" style="margin-bottom:24px">
                    <div class="form-group">
                        <label for="field_password" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">设置登录密码 *</label>
                        <input type="password" id="field_password" name="password" required minlength="6" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box" placeholder="至少6位">
                        <div style="font-size:12px;color:#94a3b8;margin-top:4px">审核通过后使用此密码登录代理商管理中心</div>
                    </div>
                    <div class="form-group">
                        <label for="field_confirm_password" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#475569">确认登录密码 *</label>
                        <input type="password" id="field_confirm_password" name="confirm_password" required minlength="6" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;box-sizing:border-box" placeholder="再次输入密码">
                    </div>
                </div>

                <button type="submit" class="btn btn-success" style="width:100%;padding:14px;font-size:16px;justify-content:center">提交入驻申请</button>

                <div style="margin-top:20px;padding:16px;background:#f8fafc;border-radius:8px;font-size:13px;color:#64748b;line-height:1.8">
                    <strong>提交须知：</strong><br>
                    1. 提交后我们将在1-3个工作日内完成审核<br>
                    2. 审核通过后，您可以使用设置的手机号和密码登录 <a href="agent/login.php">代理商管理中心</a><br>
                    3. 审核结果将通过邮件通知您，也可以随时<a href="apply-status.php">查询申请状态</a><br>
                    4. 如需修改已提交的信息，请联系我们
                </div>

                <!-- TASK-27: 信任陈述 -->
                <div style="margin-top:24px;padding:24px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px">
                    <h3 style="font-size:16px;margin-bottom:16px;color:#15803d">为什么选择我们？</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px;color:#475569">
                        <div style="padding:12px;background:#fff;border-radius:8px;text-align:center">
                            <div style="font-size:22px;font-weight:700;color:var(--success)">✦</div>
                            <strong>500+ 合作伙伴</strong>
                            <p style="font-size:12px;color:#94a3b8;margin-top:4px">全国已有超过 500 家授权合作伙伴</p>
                        </div>
                        <div style="padding:12px;background:#fff;border-radius:8px;text-align:center">
                            <div style="font-size:22px;font-weight:700;color:var(--success)">✦</div>
                            <strong>收益增长 300%</strong>
                            <p style="font-size:12px;color:#94a3b8;margin-top:4px">合作伙伴平均收益增长 3 倍</p>
                        </div>
                        <div style="padding:12px;background:#fff;border-radius:8px;text-align:center">
                            <div style="font-size:22px;font-weight:700;color:var(--success)">✦</div>
                            <strong>品牌授权保障</strong>
                            <p style="font-size:12px;color:#94a3b8;margin-top:4px">完善的授权体系，正品保障</p>
                        </div>
                        <div style="padding:12px;background:#fff;border-radius:8px;text-align:center">
                            <div style="font-size:22px;font-weight:700;color:var(--success)">✦</div>
                            <strong>全程培训支持</strong>
                            <p style="font-size:12px;color:#94a3b8;margin-top:4px">从入门到精通，一对一辅导</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
