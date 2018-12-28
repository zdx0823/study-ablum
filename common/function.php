<?php

/**
 * 读取配置
 * @param string $name 配置项
 * @return mixed 配置值
 */
function config($name)
{
    static $config = null;
    if (!$config) {
        $config = require './common/config.php';
    }
    return isset($config[$name]) ? $config[$name] : '';
}

/**
 * 接收输入的函数
 * @param array $method 输入的数组（可用字符串get、post来表示）
 * @param string $name 从数组中取出的变量名
 * @param string $type 表示类型的字符串
 * @param mixed $default 变量不存在时使用的默认值
 * @return mixed 返回的结果
 */
function input($method, $name, $type = 's', $default = '')
{
    switch ($method) {
        case 'get': $method = $_GET;
            break;
        case 'post': $method = $_POST;
            break;
    }
    $data = isset($method[$name]) ? $method[$name] : $default;
    switch ($type) {
        case 's': return is_string($data) ? $data : $default;
        case 'd': return (int) $data;
        case 'a': return is_array($data) ? $data : [];
        default: trigger_error('不存在的过滤类型“' . $type . '”');
    }
}

/**
 * 保存错误信息
 * @param string $str 错误信息
 * @return string 错误信息
 */
function tips($str = null)
{
    static $tips = null;
    return $str ? ($tips = $str) : $tips;
}


/**
 * 检查上传文件
 * @param array $file 上传文件的 $_FILES['xx'] 数组
 * @return string 检查通过返回true，否则返回错误信息
 */
function upload_check($file)
{
    $error = isset($file['error']) ? $file['error'] : UPLOAD_ERR_NO_FILE;
    switch ($error) {
        case UPLOAD_ERR_OK:
            return is_uploaded_file($file['tmp_name']) ?: '非法文件';
        case UPLOAD_ERR_INI_SIZE:
            return '文件大小超过了服务器设置的限制！';
        case UPLOAD_ERR_FORM_SIZE:
            return '文件大小超过了表单设置的限制！';
        case UPLOAD_ERR_PARTIAL:
            return '文件只有部分被上传！';
        case UPLOAD_ERR_NO_FILE:
            return '没有文件被上传！';
        case UPLOAD_ERR_NO_TMP_DIR:
            return '上传文件临时目录不存在！';
        case UPLOAD_ERR_CANT_WRITE:
            return '文件写入失败！';
        default:
            return '未知错误';
    }
}

/**
 * 生成缩略图（最大裁剪）
 * @param string $file 原图的路径
 * @param string $save 缩略图的保存路径
 * @param int $limit 缩略图的边长（像素）
 * @return bool 成功返回true，失败返回false
 */
function thumb($file, $save, $limit)
{
    $func = [
        'image/png' => function ($file, $img = null) {
            return $img ? imagepng($img, $file) : imagecreatefrompng($file);
        },
        'image/jpeg' => function ($file, $img = null) {
            return $img ? imagejpeg($img, $file, 100) : imagecreatefromjpeg($file);
        }
    ];
    $info = getimagesize($file);
    list($width, $height) = $info;
    $mime = $info['mime'];
    if (!in_array($mime, ['image/png', 'image/jpeg'])) {
        trigger_error('创建缩略图失败，不支持的图片类型。', E_USER_WARNING);
        return false;
    }
    $img = $func[$mime]($file);
    if ($width > $height) {
        $size = $height;
        $x = (int) (($width - $height) / 2);
        $y = 0;
    } else {
        $size = $width;
        $x = 0;
        $y = (int) (($height - $width) / 2);
    }
    $thumb = imagecreatetruecolor($limit, $limit);
    imagecopyresampled($thumb, $img, 0, 0, $x, $y, $limit, $limit, $size, $size);
    return $func[$mime]($save, $thumb);
}
