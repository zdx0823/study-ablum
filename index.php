<?php
require './common/init.php';
// 接收参数
$id = input('get', 'id', 'd');
$sort = input('get', 'sort', 's');
$action = input('post', 'action', 's');
// 判断相册是否存在
if ($id && !album_data($id)) {
    exit('相册不存在！');
}
// 新建相册
if ($action == 'new') {
    album_new($id, input('post', 'name', 's'));
}
// 上传图片
elseif ($action == 'upload') {
    album_upload($id, input($_FILES, 'upload', 'a'));
}
// 删除相册
elseif ($action == 'delete') {
    album_delete(input('post', 'action_id', 'd'));
}
// 设为封面
elseif ($action == 'pic_cover') {
    album_picture_cover(input('post', 'action_id', 'd'), $id);
}
// 删除图片
elseif ($action == 'pic_delete') {
    album_picture_delete(input('post', 'action_id', 'd'));
}
// 查询相册名称作为网页标题
$title = album_data($id)['name'] ?: '首页';
// 查询导航栏
$nav = album_nav($id);
// 查询相册列表
$list = album_list($id, $sort);
// 载入模板






require './view/index.html';