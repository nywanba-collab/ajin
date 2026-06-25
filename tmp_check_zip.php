<?php
$z = new ZipArchive();
if ($z->open('auth-server/storage/updates/zanmeng-auth-覆盖包.zip') === true) {
    for ($i = 0; $i < $z->numFiles; $i++) {
        echo $z->getNameIndex($i) . "\n";
    }
    echo "大小: " . round(filesize('auth-server/storage/updates/zanmeng-auth-覆盖包.zip')/1024, 1) . " KB\n";
    $z->close();
} else {
    echo "无法打开\n";
}
