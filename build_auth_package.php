<?php
/**
 * 赞盟授权端 - 打包脚本
 * 生成紫端完整安装包
 */

if (!extension_loaded('zip')) {
    die("错误：需要 PHP zip 扩展\n");
}

define('AUTH_DIR', __DIR__ . '/auth-server');

$exclude = [
    '/\.gitkeep$/',
    '/\.DS_Store/',
    '/zanmeng-auth-.*\.zip$/',
    '/update-v.*\.zip$/',
];

function shouldExclude($path) {
    global $exclude;
    foreach ($exclude as $p) {
        if (preg_match($p, $path)) return true;
    }
    return false;
}

function addDirToZip($zip, $sourceDir, $zipPrefix = '') {
    $dir = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
    $count = 0;
    foreach ($iter as $file) {
        $relPath = str_replace('\\', '/', substr($file->getRealPath(), strlen(realpath($sourceDir)) + 1));
        if (shouldExclude($relPath)) continue;
        if ($file->isDir()) {
            $zip->addEmptyDir($zipPrefix . $relPath);
        } else {
            $zip->addFile($file->getRealPath(), $zipPrefix . $relPath);
            $count++;
        }
    }
    return $count;
}

$outputZip = __DIR__ . '/zanmeng-auth-v1.0.3-full.zip';
@unlink($outputZip);

$zip = new ZipArchive();
if ($zip->open($outputZip, ZipArchive::CREATE) !== true) {
    die("无法创建 ZIP: $outputZip\n");
}

$count = addDirToZip($zip, AUTH_DIR, '');
$zip->close();

echo "紫端安装包打包完成！\n";
echo "  文件数: $count\n";
echo "  大小: " . round(filesize($outputZip)/1024/1024, 2) . " MB\n";
echo "  输出: $outputZip\n";
