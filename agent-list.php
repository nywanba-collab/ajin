<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = DB::getInstance();

$pageTitle = '代理商列表 - ' . getSetting('site_name');

$level = isset($_GET['level']) ? trim($_GET['level']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$where = ["`status` = 1"];
$params = [];

if ($keyword) {
    $where[] = "(`name` LIKE ? OR `phone` LIKE ? OR `wechat` LIKE ? OR `contact_person` LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($level) {
    $where[] = "`level` = ?";
    $params[] = $level;
}

if ($city) {
    $where[] = "(`city` LIKE ? OR `address` LIKE ?)";
    $params[] = "%$city%";
    $params[] = "%$city%";
}

$whereSql = implode(' AND ', $where);

$agents = [];
try {
    $agents = $db->fetchAll("SELECT * FROM `agents` WHERE $whereSql ORDER BY `level` ASC", $params);
} catch (Exception $e) {
    try {
        $agents = $db->fetchAll("SELECT * FROM `agents` WHERE $whereSql", $params);
    } catch (Exception $e2) {
        $agents = [];
    }
}

$cities = [];
try {
    $cities = $db->fetchAll("SELECT DISTINCT `city` as city FROM `agents` WHERE `status` = 1 AND `city` IS NOT NULL AND `city` != '' ORDER BY `city`");
} catch (Exception $e) {
    $cities = [];
}

if (empty($cities)) {
    try {
        $cities = $db->fetchAll("SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(`address`, '市', 1), '省', -1) as city FROM `agents` WHERE `status` = 1 AND `address` LIKE '%市%' ORDER BY city");
    } catch (Exception $e) {
        $cities = [];
    }
}

$levels = [
    1 => '普通代理商',
    2 => '高级代理商',
    3 => '金牌代理商',
    4 => '战略合作伙伴'
];
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.filter-bar { background:#FFF9F0;border:1px solid #E8D5A3;border-radius:8px;padding:20px;margin-bottom:24px }
.filter-row { display:grid;grid-template-columns:repeat(4,1fr);gap:16px }
@media (max-width: 992px) { .filter-row { grid-template-columns:repeat(2,1fr) } }
@media (max-width: 576px) { .filter-row { grid-template-columns:1fr } }
.agent-list { display:grid;grid-template-columns:repeat(3,1fr);gap:20px }
@media (max-width: 992px) { .agent-list { grid-template-columns:repeat(2,1fr) } }
@media (max-width: 576px) { .agent-list { grid-template-columns:1fr } }
.agent-card { background:#FFF9F0;border:1px solid #E8D5A3;border-radius:8px;padding:24px;transition:all 0.25s }
.agent-card:hover { border-color:#D4A843;box-shadow:0 4px 20px rgba(184,134,11,0.1) }
.agent-card .header { display:flex;justify-content:space-between;align-items:start;margin-bottom:16px }
.agent-card .name { font-size:18px;font-weight:700;color:#2C1810;margin:0 0 4px }
.badge-level { padding:3px 12px;border-radius:4px;font-size:12px;font-weight:600 }
.badge-1 { background:#F5EDD6;color:#8B6914 }
.badge-2 { background:#F5EDD6;color:#B8860B }
.badge-3 { background:#E8D5A3;color:#8B6914 }
.badge-4 { background:#D4A843;color:#2C1810 }
.rating { color:#B8860B;font-size:14px;margin-bottom:12px }
.info-row { display:flex;align-items:center;margin-bottom:8px;color:#8B7355;font-size:14px }
.view-btn { display:block;width:100%;text-align:center;padding:10px;background:linear-gradient(135deg,#D4A843,#B8860B);color:#FFF9F0;border-radius:4px;text-decoration:none;font-weight:600;margin-top:16px;transition:all 0.2s }
.view-btn:hover { background:linear-gradient(135deg,#E8D5A3,#D4A843);color:#1A0F0A }
.empty-state { text-align:center;padding:60px;color:#8B7355 }
</style>

<div style="max-width:1200px;margin:0 auto;padding:24px 20px">
    <div style="margin-bottom:24px">
        <h1 style="font-size:28px;font-weight:700;color:#2C1810;margin:0 0 8px">代理商列表</h1>
        <p style="color:#8B7355">查找您所在地区的授权代理商，共找到 <?= count($agents) ?> 家</p>
    </div>

    <div class="filter-bar">
        <form method="GET">
            <div class="filter-row">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;color:#5C4033">关键词搜索</label>
                    <input type="text" name="keyword" value="<?= h($keyword) ?>" placeholder="名称/手机号/微信号/联系人" style="width:100%;padding:10px 14px;border:1px solid #E8D5A3;border-radius:4px;background:#FFF9F0;color:#2C1810">
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;color:#5C4033">等级</label>
                    <select name="level" style="width:100%;padding:10px 14px;border:1px solid #E8D5A3;border-radius:4px;background:#FFF9F0;color:#2C1810">
                        <option value="">全部等级</option>
                        <?php foreach ($levels as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $level == $key ? 'selected' : '' ?>><?= $val ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;color:#5C4033">城市</label>
                    <select name="city" style="width:100%;padding:10px 14px;border:1px solid #E8D5A3;border-radius:4px;background:#FFF9F0;color:#2C1810">
                        <option value="">全部城市</option>
                        <?php foreach ($cities as $c): ?>
                        <?php if (!empty($c['city'])): ?>
                        <option value="<?= h($c['city']) ?>" <?= $city == $c['city'] ? 'selected' : '' ?>><?= h($c['city']) ?>市</option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end">
                    <button type="submit" style="width:100%;padding:10px;background:linear-gradient(135deg,#D4A843,#B8860B);color:#FFF9F0;border:none;border-radius:4px;font-weight:600;cursor:pointer;transition:all 0.2s">搜索</button>
                </div>
            </div>
        </form>
    </div>

    <?php if (empty($agents)): ?>
        <div class="empty-state">
            <h3 style="margin-bottom:8px;color:#2C1810">没有找到匹配的代理商</h3>
            <p>请尝试调整筛选条件或搜索其他关键词</p>
            <a href="agent-list.php" style="display:inline-block;margin-top:16px;padding:10px 24px;background:linear-gradient(135deg,#D4A843,#B8860B);color:#FFF9F0;border-radius:4px;text-decoration:none;font-weight:600">查看全部代理商</a>
        </div>
    <?php else: ?>
    <div class="agent-list">
        <?php foreach ($agents as $agent): ?>
        <div class="agent-card">
            <div class="header">
                <div>
                    <h3 class="name"><?= h($agent['name']) ?></h3>
                    <span class="badge-level badge-<?= intval($agent['level']) ?>">
                        <?php 
                        $levelText = $levels[$agent['level']] ?? '代理商';
                        echo h($levelText);
                        ?>
                    </span>
                </div>
                <?php if (isset($agent['rating']) && $agent['rating'] > 0): ?>
                <div class="rating">
                    <?= str_repeat('★', intval($agent['rating'] ?? 0)) ?>
                    <?= str_repeat('☆', 5 - intval($agent['rating'] ?? 0)) ?>
                    <?= number_format($agent['rating'] ?? 0, 1) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="info-row">
                <span><?= h(getAgentContact($agent, 'phone')) ?></span>
            </div>
            <?php if (!empty($agent['contact_person'])): ?>
            <div class="info-row">
                <span><?= h($agent['contact_person']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($agent['wechat'])): ?>
            <div class="info-row">
                <span><?= h(getAgentContact($agent, 'wechat')) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($agent['address'])): ?>
            <div class="info-row">
                <span><?= h($agent['address']) ?></span>
            </div>
            <?php endif; ?>
            <a href="agent-detail.php?id=<?= $agent['id'] ?>" class="view-btn">查看详情</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
