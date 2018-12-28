<?php

/**
 * 查询相册记录（缓存查询结果）
 * @param int $id 相册ID
 * @return array 查询结果数组，不存在时返回false
 */
function album_data($id)
{
    static $data = [0 => false];
    if (!isset($data[$id])) {
        $data[$id] = db_fetch_row("SELECT `pid`,`path`,`name`,`cover`,`total` FROM `album` WHERE `id`=$id") ?: false;
    }
    return $data[$id];
}

/**
 * 查询相册层级导航
 * @param int $id 相册ID
 * @return array 查询结果数组，不存在时返回空数组
 */
function album_nav($id)
{
    $path = preg_replace('/^0,/', '', (album_data($id)['path'] . $id));
    return $path ? db_fetch_all("SELECT `id`,`name` FROM `album` WHERE `id` IN ($path) ORDER BY FIELD(`id`,$path)") : [];
}

/**
 * 查询当前相册所有的子相册和图片
 * @param int $id 相册ID
 * @param string $sort 排序（new、old）
 * @return array 查询结果数组
 */
function album_list($id, $sort)
{
    $sort = ($sort == 'old') ? 'ASC' : 'DESC';
    return [
        'album' => db_fetch_all("SELECT `id`,`name`,`cover`,`total` FROM `album` WHERE `pid`=$id  ORDER BY `id` $sort"),
        'picture' => db_fetch_all("SELECT `id`,`name`,`save` FROM `picture` WHERE `pid`=$id ORDER BY `id` $sort")
    ];
}

/**
 * 创建相册
 * @param int $pid 新相册的上级目录ID
 * @param string $name 新相册的名称
 */
function album_new($pid, $name)
{
    $data = album_data($pid);
    if (substr_count($data['path'], ',') >= config('LEVEL_MAX')) {
        return tips('无法继续创建子目录，已经达到最多层级！');
    }
 /* if (!preg_match('/^\w{1,12}$/', $name)) {
        return tips('无法创建相册，只允许1~12位字母、数字、下划线组成。');
    } */
    $name = mb_strimwidth(trim($name), 0, 12);
    db_exec('INSERT INTO `album` (`pid`,`path`,`name`) VALUES (?,?,?)', 'iss', [
        $pid, ($data['path'] . $pid . ','), ($name ?: '未命名')
    ]);
}

/**
 * 上传图片
 * @param int $pid 图片所属的相册ID
 * @param array $file 上传文件 $_FILES['xx'] 数组
 */
function album_upload($pid, $file)
{
    // 检查文件是否上传成功
    if (true !== ($error = upload_check($file))) {
        return tips("文件上传失败：$error");
    }
    // 检查文件类型是否正确
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), config('ALLOW_EXT'))) {
        return tips('文件上传失败：只允许扩展名：' . implode(', ', config('ALLOW_EXT')));
    }
    // 生成文件名和保存路径
    $new_dir = date('Y-m/d');                       // 生成子目录
    $new_name = md5(microtime(true)) . ".$ext";     // 生成文件名
    // 创建原图保存目录
    $upload_dir = "./uploads/$new_dir";
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
        return tips('文件上传失败：无法创建保存目录！');
    }
    // 创建缩略图保存目录
    $thumb_dir = "./thumbs/$new_dir";
    if (!is_dir($thumb_dir) && !mkdir($thumb_dir, 0777, true)) {
        return tips('文件上传失败：无法创建缩略图保存目录！');
    }
    // 保存上传文件
    if (!move_uploaded_file($file['tmp_name'], "$upload_dir/$new_name")) {
        return tips('文件上传失败：无法保存文件！');
    }
    // 创建缩略图
    thumb("$upload_dir/$new_name", "$thumb_dir/$new_name", config('THUMB_SIZE'));
    // 保存到数据库
    $name = mb_strimwidth(trim(pathinfo($file['name'], PATHINFO_FILENAME)), 0, 80);
    db_exec('INSERT INTO `picture` (`pid`,`name`,`save`) VALUES (?,?,?)', 'iss', [$pid, $name, "$new_dir/$new_name"]);
    $pid && album_total($pid, '+1');
}

/**
 * 修改相册的total字段
 * @param int $id 相册ID
 * @param string $method 操作（+1、-1）
 */
function album_total($id, $method = '+1')
{
    $path = preg_replace('/^0,/', '', (album_data($id)['path'] . $id));
    $path && db_exec("UPDATE `album` SET `total`=`total`$method WHERE `id` IN ($path)");
}

/**
 * 删除相册
 * @param int $id 相册ID
 */
function album_delete($id)
{
    $data = album_data($id);
    if ($data['total'] > 0) {
        return tips('删除失败：只能删除空相册！');
    }
    if (db_fetch_row("SELECT 1 FROM `album` WHERE `pid`=$id")) {
        return tips('删除失败：该相册含有子相册！');
    }
    db_exec("DELETE FROM `album` WHERE `id`=$id");
    $data['cover'] && is_file("./covers/{$data['cover']}") && unlink("./covers/{$data['cover']}");
}

/**
 * 查询图片记录
 * @param int $id 图片ID
 * @return array 查询结果数组，不存在时返回null
 */
function album_picture_data($id)
{
    return db_fetch_row("SELECT `pid`,`name`,`save` FROM `picture` WHERE `id`=$id");
}

/**
 * 设置图片为相册封面
 * @param int $id 图片ID
 * @param int $pid 相册ID
 */
function album_picture_cover($id, $pid)
{
    if (!$data = album_picture_data($id)) {
        return tips('设置失败：图片不存在！');
    }
    $cover_dir = './covers/' . dirname($data['save']);
    if (!is_dir($cover_dir) && !mkdir($cover_dir, 0777, true)) {
        return tips('设置失败：无法创建封面图保存目录！');
    }
    $cover_del = album_data($pid)['cover'];
    is_file("./covers/$cover_del") && unlink("./covers/$cover_del");
    copy("./thumbs/{$data['save']}", "./covers/{$data['save']}");
    db_exec("UPDATE `album` SET `cover`=? WHERE `id`=?", 'si', [$data['save'], $pid]);
    tips('设置成功！');
}

/**
 * 删除图片
 * @param int $id 图片ID
 */
function album_picture_delete($id)
{
    if (!$data = album_picture_data($id)) {
        return tips('删除失败：图片不存在！');
    }
    db_exec("DELETE FROM `picture` WHERE `id`=$id");
    is_file("./thumbs/{$data['save']}") && unlink("./thumbs/{$data['save']}");
    is_file("./uploads/{$data['save']}") && unlink("./uploads/{$data['save']}"); 
    $data['pid'] && album_total($data['pid'], '-1');
}
