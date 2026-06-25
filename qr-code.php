<?php
require_once __DIR__ . '/includes/config.php';

$id = intval($_GET['id'] ?? 0);
$download = isset($_GET['download']);

if ($id <= 0) {
    echo '无效的代理商ID';
    exit;
}

$url = SITE_URL . '/agent-detail.php?id=' . $id;

$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($url);

header('Location: ' . $qrApiUrl);
exit;
?>