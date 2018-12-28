<?php
require './common/init.php';
$start = 0;  // 开始位置
$size = 100; // 每次检查 100 条记录
$data = db_fetch_all("SELECT `id`,`pid`,`path`,`total` FROM `album` LIMIT $start,$size");
foreach ($data as $v) {
    $result = [];
    album_tree($v['id'], $result);
    // 核对total字段
    $pids = implode(',', array_keys($result));
    $total = db_fetch_row("SELECT COUNT(*) as `t` FROM `picture` WHERE `pid` IN ($pids)")['t'];
    if ($v['total'] != $total) {
        echo "ID={$v['id']}的total字段有误，修复";
        echo db_exec("UPDATE `album` SET `total`='$total' WHERE `id`={$v['id']}") ? '成功' : '<b>失败</b>', '。<br>';
    }
    // 核对path字段
    $path = album_path($v['id']);
    if ($v['path'] != $path) {
        echo "ID={$v['id']}的path字段有误，修复";
        echo db_exec("UPDATE `album` SET `path`='$path' WHERE `id`={$v['id']}") ? '成功' : '<b>失败</b>', '。<br>';
    }
}
echo '第' . ($start + 1) . '～' . ($start + $size) .'条记录检查完成。';
// 递归查找子相册
function album_tree($id, &$result) {
    if (isset($result[$id])) {
        exit("发现相册id $id 路径异常，请手动修复。");
    }
    $result[$id] = true;
    foreach (db_fetch_all("SELECT `id` FROM `album` WHERE `pid`=$id") as $v) {
        album_tree($v['id'], $result);
    }
}
// 向上查找相册路径
function album_path($id) {
    $path = '';
    while ($id = db_fetch_row("SELECT `pid` FROM `album` WHERE `id`=$id")['pid']) {
        $path = "$id,$path";
    }
    if ($id === null) {
        exit('发现相册pid ' . strstr($path, ',', true) . ' 不存在，请手动修复。');
    }
    return "0,$path";
}
