<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = intval($_GET['id'] ?? 0);

$db = DB::getInstance();
$agent = $db->fetchOne("SELECT * FROM `agents` WHERE `id` = ?", [$id]);

if (!$agent) {
    http_response_code(404);
    echo '代理商不存在';
    exit;
}

$siteName = getSetting('site_name') ?: '品牌授权中心';
$levelName = agentLevelName($agent['level']);
$certNumber = $agent['cert_number'] ?: 'AUTH-' . str_pad($agent['id'], 6, '0', STR_PAD_LEFT);

if (isset($_GET['download'])) {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="certificate_' . $id . '.html"');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权证书 - <?= h($agent['name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Microsoft YaHei", "SimHei", sans-serif;
            background: linear-gradient(135deg, #1A0F0A 0%, #2C1810 100%);
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .certificate {
            width: 800px;
            background: #FFF9F0;
            padding: 50px 60px;
            position: relative;
            box-shadow: 0 20px 60px rgba(26,15,10,0.4);
        }
        .border-outer {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 3px solid #B8860B;
            pointer-events: none;
        }
        .border-inner {
            position: absolute;
            top: 18px;
            left: 18px;
            right: 18px;
            bottom: 18px;
            border: 1px solid #E8D5A3;
            pointer-events: none;
        }
        .corner {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 4px solid #B8860B;
            pointer-events: none;
        }
        .corner-tl { top: 25px; left: 25px; border-right: none; border-bottom: none; }
        .corner-tr { top: 25px; right: 25px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 25px; left: 25px; border-right: none; border-top: none; }
        .corner-br { bottom: 25px; right: 25px; border-left: none; border-top: none; }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 44px;
            margin-bottom: 8px;
            color: #B8860B;
        }
        .company-name {
            font-size: 20px;
            color: #8B6914;
            font-weight: bold;
            letter-spacing: 4px;
        }
        .title {
            text-align: center;
            font-size: 52px;
            font-weight: bold;
            color: #B8860B;
            margin-bottom: 30px;
            letter-spacing: 20px;
        }
        .subtitle {
            text-align: center;
            font-size: 15px;
            color: #C4A882;
            margin-bottom: 30px;
            letter-spacing: 2px;
        }
        .cert-no {
            text-align: center;
            font-size: 14px;
            color: #8B7355;
            margin-bottom: 30px;
        }
        .content {
            padding: 0 40px;
        }
        .intro {
            text-align: center;
            font-size: 16px;
            color: #8B7355;
            margin-bottom: 24px;
        }
        .agent-name {
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            color: #2C1810;
            margin-bottom: 30px;
            letter-spacing: 8px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 36px;
        }
        .info-table tr {
            border-bottom: 1px dashed #E8D5A3;
        }
        .info-table td {
            padding: 15px 10px;
            font-size: 16px;
        }
        .info-table .label {
            width: 120px;
            color: #8B7355;
        }
        .info-table .value {
            color: #2C1810;
            font-weight: bold;
        }
        .level-badge {
            display: inline-block;
            background: linear-gradient(135deg, #D4A843, #B8860B);
            color: #FFF9F0;
            padding: 8px 30px;
            border-radius: 4px;
            font-weight: bold;
        }
        .validity {
            text-align: center;
            font-size: 14px;
            color: #8B7355;
            padding-top: 20px;
            border-top: 1px solid #E8D5A3;
        }
        .stamp {
            position: absolute;
            right: 80px;
            bottom: 80px;
            width: 100px;
            height: 100px;
            border: 4px solid #B8860B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            font-weight: bold;
            color: #B8860B;
            transform: rotate(-15deg);
            opacity: 0.85;
            background: rgba(255,249,240,0.9);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #C4A882;
            font-size: 12px;
        }
        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .btn {
            background: #2C1810;
            color: #E8D5A3;
            padding: 12px 28px;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-family: inherit;
            transition: all 0.2s;
        }
        .btn:hover { background: #5C4033; color: #F5EDD6; }
        .btn-primary {
            background: linear-gradient(135deg, #D4A843, #B8860B);
            color: #FFF9F0;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #E8D5A3, #D4A843);
            color: #1A0F0A;
        }
        .btn-success {
            background: linear-gradient(135deg, #7C6B2B, #5C4D1E);
            color: #F5EDD6;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #9A8535, #7C6B2B);
            color: #FFF9F0;
        }
        @media print {
            body { background: #FFF9F0; padding: 0; }
            .actions { display: none; }
            .certificate { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">打印证书</button>
        <button class="btn btn-success" onclick="downloadAsPDF()">下载PDF</button>
        <a class="btn" href="agent-detail.php?id=<?= $agent['id'] ?>">返回</a>
    </div>
    
    <div class="certificate">
        <div class="border-outer"></div>
        <div class="border-inner"></div>
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>
        
        <div class="header">
            <div class="logo" style="font-size:44px;color:#B8860B">&#9670;</div>
            <div class="company-name"><?= h($siteName) ?></div>
        </div>
        
        <div class="title">授 权 证 书</div>
        
        <div class="subtitle">OFFICIAL AUTHORIZATION CERTIFICATE</div>
        
        <div class="cert-no">证书编号：<?= h($certNumber) ?></div>
        
        <div class="content">
            <div class="intro">兹授予</div>
            
            <div class="agent-name"><?= h($agent['name']) ?></div>
            
            <table class="info-table">
                <tr>
                    <td class="label">授权级别</td>
                    <td class="value"><span class="level-badge"><?= h($levelName) ?></span></td>
                </tr>
                <tr>
                    <td class="label">授权期限</td>
                    <td class="value"><?= h($agent['authorized_at'] ?: '-') ?> 至 <?= h($agent['expire_at'] ?: '长期有效') ?></td>
                </tr>
            </table>
            
            <div class="validity">
                本证书由 <strong><?= h($siteName) ?></strong> 官方颁发<br>
                可通过官网查询验证：<?= h(SITE_URL) ?>
            </div>
        </div>
        
        <div class="stamp">
            <div>官方<br>认证</div>
        </div>
        
        <div class="footer">
            生成时间：<?= date('Y-m-d H:i:s') ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    function downloadAsPDF() {
        const certificate = document.querySelector('.certificate');
        html2canvas(certificate, {
            scale: 2,
            useCORS: true,
            logging: false
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF({
                orientation: 'landscape',
                unit: 'px',
                format: [canvas.width, canvas.height]
            });
            pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
            pdf.save('certificate_<?= $agent['id'] ?>.pdf');
        }).catch(err => {
            console.error('PDF生成失败:', err);
            alert('PDF生成失败，请尝试使用打印功能导出');
        });
    }
    </script>
</body>
</html>
