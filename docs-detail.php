<?php
$pageTitle = '';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// 重定向到 docs.php?id=xxx
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    header("Location: docs.php?id=" . $id);
    exit;
}
header("Location: docs.php");
exit;
