<?php
/**
 * 代理商 - 安装预检查脚本
 * 用于在正式安装前快速检测环境是否满足要求
 */

header('Content-Type: text/html; charset=utf-8');

$checks = [];
$passed = 0;
$failed = 0;

function addCheck($name, $result, $required = true, $detail = '') {
    global $checks, $passed, $failed;
    $status = $result ? 'ok' : 'error';
    if ($result) $passed++;
    elseif ($required) $failed++;
    $checks[] = [
        'name' => $name,
        'status' => $status,
        'required' => $required,
        'detail' => $detail,
    ];
}

// PHP 版本
$phpVersion = PHP_VERSION;
$phpRequired = '8.0.0';
addCheck(
    "PHP 版本 (当前: $phpVersion)",
    version_compare($phpVersion, $phpRequired, '>='),
    true,
    "需要 >= $phpRequired"
);

// 必需扩展
$extensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json', 'fileinfo', 'session'];
foreach ($extensions as $ext) {
    addCheck(
        "PHP 扩展: $ext",
        extension_loaded($ext),
        true,
        extension_loaded($ext) ? '已加载' : '未加载'
    );
}

// 可选扩展
$optionalExts = ['curl', 'openssl', 'zip'];
foreach ($optionalExts as $ext) {
    addCheck(
        "PHP 扩展: $ext (推荐)",
        extension_loaded($ext),
        false,
        extension_loaded($ext) ? '已加载' : '未加载'
    );
}

// 目录可写检查
$dirs = [
    'uploads' => __DIR__ . '/uploads',
    'includes' => __DIR__ . '/includes',
];
foreach ($dirs as $name => $dir) {
    $isWritable = is_writable($dir);
    addCheck(
        "目录可写: $name",
        $isWritable,
        true,
        $isWritable ? '可写' : '不可写'
    );
}

// 配置文件可写
$configFile = __DIR__ . '/includes/config.php';
$configDir = dirname($configFile);
$configWritable = is_writable($configFile) || (!file_exists($configFile) && is_writable($configDir));
addCheck(
    '配置文件可写',
    $configWritable,
    true,
    $configWritable ? '可写' : '不可写'
);

// 安装向导文件存在
$installScript = __DIR__ . '/installer/install.php';
$installExists = file_exists($installScript);
addCheck(
    '安装向导文件',
    $installExists,
    true,
    $installExists ? '存在' : '缺失'
);

// 数据库结构文件
$dbSql = __DIR__ . '/installer/database.sql';
$dbSqlExists = file_exists($dbSql);
addCheck(
    '数据库结构文件',
    $dbSqlExists,
    true,
    $dbSqlExists ? '存在' : '缺失'
);

// 配置模板
$configTemplate = __DIR__ . '/installer/config.template.php';
$configTemplateExists = file_exists($configTemplate);
addCheck(
    '配置模板文件',
    $configTemplateExists,
    true,
    $configTemplateExists ? '存在' : '缺失'
);

// 检测当前域名
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$uri = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$uri = ($uri === '\\' || $uri === '/') ? '' : $uri;
$siteUrl = "{$protocol}://{$host}{$uri}";

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>代理商 - 环境检测</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            padding: 30px;
            text-align: center;
            color: #fff;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; font-weight: 600; }
        .header p { opacity: 0.9; font-size: 14px; }
        .body { padding: 30px; }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f9fafb;
        }
        .check-item.ok { background: #f0fdf4; }
        .check-item.error { background: #fef2f2; }
        .check-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .check-icon.ok { background: #22c55e; color: #fff; }
        .check-icon.error { background: #ef4444; color: #fff; }
        .check-content { flex: 1; }
        .check-name { font-weight: 500; color: #1f2937; font-size: 14px; }
        .check-detail { font-size: 12px; color: #6b7280; margin-top: 2px; }
        .summary {
            display: flex;
            gap: 16px;
            margin-top: 24px;
            padding: 20px;
            border-radius: 12px;
            background: #f9fafb;
        }
        .summary-item {
            flex: 1;
            text-align: center;
            padding: 16px;
            border-radius: 8px;
        }
        .summary-item.passed { background: #dcfce7; }
        .summary-item.failed { background: #fee2e2; }
        .summary-number {
            font-size: 32px;
            font-weight: 700;
        }
        .summary-item.passed .summary-number { color: #16a34a; }
        .summary-item.failed .summary-number { color: #dc2626; }
        .summary-label { font-size: 12px; color: #64748b; margin-top: 4px; }
        .info-box {
            margin-top: 20px;
            padding: 16px;
            background: #dbeafe;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .info-box p { color: #1e40af; font-size: 14px; line-height: 1.6; }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            justify-content: center;
        }
        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: #fff;
        }
        .btn-primary:hover { transform: translateY(-1px); }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover { background: #d1d5db; }
        .optional { font-size: 11px; color: #94a3b8; margin-left: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 代理商</h1>
            <p>环境检测 - v1.3</p>
        </div>
        <div class="body">
            <div class="section-title">📋 环境检测结果</div>

            <?php foreach ($checks as $check): ?>
            <div class="check-item <?= $check['status'] ?>">
                <div class="check-icon <?= $check['status'] ?>">
                    <?= $check['status'] === 'ok' ? '✓' : '✗' ?>
                </div>
                <div class="check-content">
                    <div class="check-name">
                        <?= htmlspecialchars($check['name']) ?>
                        <?php if (!$check['required']): ?>
                            <span class="optional">(推荐)</span>
                        <?php endif; ?>
                    </div>
                    <div class="check-detail"><?= htmlspecialchars($check['detail']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="summary">
                <div class="summary-item passed">
                    <div class="summary-number"><?= $passed ?></div>
                    <div class="summary-label">通过</div>
                </div>
                <div class="summary-item failed">
                    <div class="summary-number"><?= $failed ?></div>
                    <div class="summary-label">不满足</div>
                </div>
            </div>

            <div class="info-box">
                <p><strong>🌐 检测到的站点地址：</strong><br><?= htmlspecialchars($siteUrl) ?></p>
            </div>

            <div class="actions">
                <?php if ($failed === 0): ?>
                    <a href="installer/" class="btn btn-primary">🚀 开始安装</a>
                <?php else: ?>
                    <a href="installer/" class="btn btn-secondary">查看详细安装向导</a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($siteUrl) ?>" class="btn btn-secondary">返回首页</a>
            </div>
        </div>
    </div>
</body>
</html>
