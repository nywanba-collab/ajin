<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = '代理商详情 - ' . getSetting('site_name');

$id = intval($_GET['id'] ?? 0);
$msg = '';
$error = '';

$db = DB::getInstance();
$agent = $db->fetchOne(
    "SELECT * FROM `agents` WHERE `id` = ? AND `status` = 1 AND `audit_status` = 1",
    [$id]
);

if (!$agent) {
    http_response_code(404);
    $error = '代理商不存在或已下架';
} else {
    updateAgentViewCount($id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $agent) {
    if (isset($_POST['toggle_favorite'])) {
        $result = toggleFavoriteAgent($id);
        if ($result['action'] === 'added') {
            $msg = '已收藏';
        } else {
            $msg = '已取消收藏';
        }
        header('Location: agent-detail.php?id=' . $id . '&msg=' . urlencode($msg));
        exit;
    }
    
    if (isset($_POST['submit_review'])) {
        $nickname = trim($_POST['nickname'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content)) {
            $error = '请输入评价内容';
        } else {
            $result = addAgentReview($id, [
                'nickname' => $nickname,
                'rating' => $rating,
                'content' => $content,
            ]);
            if ($result['success']) {
                $msg = '评价已提交，等待审核';
            } else {
                $error = '提交失败，请重试';
            }
        }
    }
}

$reviews = [];
$reviewCount = 0;
$isFavorite = false;

if ($agent) {
    $reviews = getAgentReviews($id, 1, 10, 0);
    $reviewCount = getAgentReviewCount($id, 1);
    $isFavorite = isFavoriteAgent($id);
    $agent = $db->fetchOne("SELECT * FROM `agents` WHERE `id` = ?", [$id]);
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($error): ?>
<div class="alert alert-danger" style="margin:20px auto;max-width:1200px">
    <?= h($error) ?>
</div>
<?php endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success" style="margin:20px auto;max-width:1200px">
    <?= h($msg) ?>
</div>
<?php endif; ?>

<?php if ($agent): ?>
<div style="max-width:1200px;margin:0 auto;padding:20px">
    
    <!-- 代理商基本信息 -->
    <div class="data-card" style="margin-bottom:20px">
        <div style="display:flex;gap:30px;flex-wrap:wrap">
            <div style="flex:1;min-width:300px">
                <div style="display:flex;align-items:center;gap:15px;margin-bottom:20px">
                    <h1 style="margin:0"><?= h($agent['name']) ?></h1>
                    <span class="badge badge-info" style="font-size:14px;padding:6px 16px"><?= agentLevelName($agent['level']) ?></span>
                    <span class="badge <?= $agent['status'] ? 'badge-success' : 'badge-warning' ?>" style="font-size:14px;padding:6px 16px">
                        <?= $agent['status'] ? '授权中' : '已停用' ?>
                    </span>
                </div>
                
                <?php if ($agent['avg_rating'] > 0): ?>
                <div style="margin-bottom:20px;color:#f59e0b;font-size:20px">
                    <?= getRatingStars($agent['avg_rating']) ?>
                    <span style="color:#64748b;font-size:14px;margin-left:10px">
                        <?= $agent['avg_rating'] ?> 分（<?= $agent['review_count'] ?> 条评价）
                    </span>
                </div>
                <?php endif; ?>
                
                <table style="width:100%;border-collapse:collapse">
                    <?php if ($agent['cert_number']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b;width:120px">授权编号</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;font-weight:bold"><?= h($agent['cert_number']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['contact_person']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">联系人</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0"><?= h($agent['contact_person']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['phone']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">联系电话</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">📞 <?= h(getAgentContact($agent, 'phone')) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['wechat']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">微信号</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">💬 <?= h(getAgentContact($agent, 'wechat')) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['taobao_shop']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">淘宝店铺</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">🛒 <?= h(getAgentContact($agent, 'taobao_shop')) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['domain']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">官网</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">
                            🌐 <a href="https://<?= h($agent['domain']) ?>" target="_blank" style="color:#3b82f6"><?= h($agent['domain']) ?></a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['city']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">所在城市</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">📍 <?= h($agent['city']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['address']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">详细地址</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0"><?= h($agent['address']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($agent['service_area']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">服务区域</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0"><?= h($agent['service_area']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">授权起始</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0"><?= $agent['authorized_at'] ? h($agent['authorized_at']) : '-' ?></td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">到期日期</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0">
                            <?php if ($agent['expire_at']): ?>
                                <?php
                                $expireDate = new DateTime($agent['expire_at']);
                                $today = new DateTime();
                                $diff = $expireDate->diff($today);
                                if ($expireDate < $today): ?>
                                    <span style="color:#ef4444">❌ 已过期 <?= $diff->days ?> 天</span>
                                <?php elseif ($diff->days <= 30): ?>
                                    <span style="color:#f59e0b">⚠️ 还剩 <?= $diff->days ?> 天到期</span>
                                <?php else: ?>
                                    <span style="color:#10b981">✅ 有效期至 <?= h($agent['expire_at']) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($agent['description']): ?>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#64748b">简介</td>
                        <td style="padding:12px 0;border-bottom:1px solid #e2e8f0"><?= nl2br(h($agent['description'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- 右侧：授权证书、二维码 -->
            <div style="width:280px">
                <!-- 授权证书 -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px">
                    <div style="font-size:14px;color:#64748b;margin-bottom:10px">授权证书</div>
                    <?php if ($agent['cert_image']): ?>
                        <img src="<?= h($agent['cert_image']) ?>" alt="授权证书" style="max-width:100%;border-radius:8px" class="clickable-image" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:160px;background:#f8fafc;display:flex;align-items:center;justify-content:center;color:#94a3b8;border-radius:8px">
                            暂无证书图片
                        </div>
                    <?php endif; ?>
                    <div style="margin-top:15px;display:flex;gap:10px;justify-content:center">
                        <?php if ($agent['cert_image']): ?>
                        <a href="<?= h($agent['cert_image']) ?>" target="_blank" class="btn btn-sm btn-primary" style="text-decoration:none">查看证书</a>
                        <?php endif; ?>
                        <a href="certificate.php?id=<?= $agent['id'] ?>" target="_blank" class="btn btn-sm btn-success" style="text-decoration:none">生成电子证书</a>
                    </div>
                </div>
                
                <!-- 二维码 -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-align:center;margin-bottom:20px">
                    <div style="font-size:14px;color:#64748b;margin-bottom:10px">扫一扫分享</div>
                    <?php $qrData = urlencode(SITE_URL . '/agent-detail.php?id=' . $agent['id']); ?>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrData ?>" alt="二维码" style="width:200px;height:200px;border-radius:8px">
                    <div style="margin-top:15px">
                        <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrData ?>&download=1" class="btn btn-sm btn-primary" style="text-decoration:none" target="_blank">下载二维码</a>
                    </div>
                </div>
                
                <!-- 收藏按钮 -->
                <form method="POST" style="margin-bottom:20px">
                    <input type="hidden" name="toggle_favorite" value="1">
                    <button type="submit" class="btn <?= $isFavorite ? 'btn-warning' : 'btn-danger' ?>" style="width:100%">
                        <?= $isFavorite ? '❤️ 已收藏' : '🤍 收藏此代理商' ?>
                    </button>
                </form>
                
                <!-- 数据统计 -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:15px">
                        <div style="text-align:center;flex:1">
                            <div style="font-size:24px;font-weight:bold;color:#3b82f6"><?= $agent['view_count'] ?: 0 ?></div>
                            <div style="font-size:12px;color:#64748b">浏览次数</div>
                        </div>
                        <div style="text-align:center;flex:1">
                            <div style="font-size:24px;font-weight:bold;color:#ef4444"><?= $agent['favorite_count'] ?: 0 ?></div>
                            <div style="font-size:12px;color:#64748b">收藏次数</div>
                        </div>
                        <div style="text-align:center;flex:1">
                            <div style="font-size:24px;font-weight:bold;color:#10b981"><?= $agent['review_count'] ?: 0 ?></div>
                            <div style="font-size:12px;color:#64748b">评价数</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 评价区域 -->
    <div class="data-card">
        <div class="data-card-header">
            <h3>用户评价 (<?= $reviewCount ?>)</h3>
        </div>
        
        <!-- 发表评价 -->
        <div style="padding:20px;background:#f8fafc;border-radius:8px;margin:20px">
            <h4 style="margin-bottom:15px">发表评价</h4>
            <form method="POST">
                <div style="margin-bottom:15px">
                    <label for="field_rating_5" style="display:block;margin-bottom:8px;color:#64748b">评分</label>
                    <div style="display:flex;gap:10px">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <label style="cursor:pointer;display:flex;align-items:center;gap:4px">
                                <input type="radio" name="rating" value="<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?> id="field_rating_<?= $i ?>">
                                <span style="color:#f59e0b">⭐</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div style="margin-bottom:15px">
                    <label for="field_nickname" style="display:block;margin-bottom:8px;color:#64748b">昵称（选填）</label>
                    <input type="text" id="field_nickname" name="nickname" placeholder="匿名用户" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px">
                </div>
                <div style="margin-bottom:15px">
                    <label for="field_content" style="display:block;margin-bottom:8px;color:#64748b">评价内容 *</label>
                    <textarea name="content" id="field_content" rows="4" required placeholder="请输入您对该代理商的评价..." style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;resize:vertical"></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">提交评价</button>
                <span style="margin-left:15px;color:#94a3b8;font-size:13px">* 评价需要审核后显示</span>
            </form>
        </div>
        
        <!-- 评价列表 -->
        <?php if (!empty($reviews)): ?>
        <div style="padding:20px">
            <?php foreach ($reviews as $review): ?>
            <div style="padding:20px 0;border-bottom:1px solid #e2e8f0">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <div>
                        <strong><?= h($review['nickname'] ?: '匿名用户') ?></strong>
                        <span style="margin-left:15px;color:#f59e0b">
                            <?= getRatingStars($review['rating']) ?>
                        </span>
                    </div>
                    <span style="color:#94a3b8;font-size:13px"><?= $review['created_at'] ?></span>
                </div>
                <div style="color:#4b5563;line-height:1.8">
                    <?= nl2br(h($review['content'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="padding:40px;text-align:center;color:#94a3b8">
            暂无评价，快来抢沙发吧！
        </div>
        <?php endif; ?>
    </div>
    
    <!-- 返回按钮 -->
    <div style="text-align:center;margin-top:30px">
        <a href="query.php" class="btn btn-primary" style="text-decoration:none">← 返回查询</a>
    </div>
</div>

<style>
.clickable-image { cursor: pointer; }
</style>

<script>
document.querySelectorAll('.clickable-image').forEach(img => {
    img.addEventListener('click', function() {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:pointer';
        const imgEl = document.createElement('img');
        imgEl.src = this.src;
        imgEl.style.cssText = 'max-width:90%;max-height:90%;border-radius:8px';
        overlay.appendChild(imgEl);
        overlay.addEventListener('click', () => overlay.remove());
        document.body.appendChild(overlay);
    });
});
</script>

<?php elseif ($error): ?>
<div style="max-width:1200px;margin:100px auto;text-align:center">
    <div style="font-size:80px;margin-bottom:20px">😢</div>
    <h2 style="color:#64748b;margin-bottom:20px"><?= h($error) ?></h2>
    <a href="index.php" class="btn btn-primary" style="text-decoration:none">返回首页</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
