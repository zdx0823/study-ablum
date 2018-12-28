<?php
return [
    // 数据库连接配置
    'DB_CONNECT' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => 'root',
        'dbname' => 'ablum',
        'port' => '3306'
    ],
    // 数据库字符集
    'DB_CHARSET' => 'utf8',
    // 相册层级最大值
    'LEVEL_MAX' => 5,
    // 允许的图片扩展名（小写）
    'ALLOW_EXT' => ['jpg', 'jpeg', 'png'],
    // 缩略图大小
    'THUMB_SIZE' => 260,
];