<?php

/**
 * 连接数据库
 * @return mysqli 数据库连接
 */
function db_connect()
{
    static $link = null;
    if (!$link) {
        $config = array_merge(['host' => '', 'user' => '', 'pass' => '', 'dbname' => '', 'port' => ''], config('DB_CONNECT'));
        if (!$link = call_user_func_array('mysqli_connect', $config)) {
            exit('数据库连接失败：' . mysqli_connect_error());
        }
        mysqli_set_charset($link, config('DB_CHARSET'));
    }
    return $link;
}

/**
 * 执行SQL语句
 * @param string $sql SQL语句
 * @param string $type 参数绑定的数据类型（i、d、s、b）
 * @param array $data 参数绑定的数据
 * @return mysqli_stmt
 */
function db_query($sql, $type = '', array $data = [])
{
    $link = db_connect();
    if (!$stmt = mysqli_prepare($link, $sql)) {
        exit("SQL[$sql]预处理失败：" . mysqli_error($link));
    }
    if (!empty($data)) {
        $args = [$stmt, $type];
        foreach ($data as &$args[]);
        call_user_func_array('mysqli_stmt_bind_param', $args);
    }
    if (!mysqli_stmt_execute($stmt)) {
        exit('数据库操作失败：' . mysqli_stmt_error($stmt));
    }
    return $stmt;
}

function db_fetch_all($sql, $type = '', array $data = [])
{
    $stmt = db_query($sql, $type, $data);
    return mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
}

function db_fetch_row($sql, $type = '', array $data = [])
{
    $stmt = db_query($sql, $type, $data);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function db_exec($sql, $type = '', array $data = [])
{
    $stmt = db_query($sql, $type, $data);
    return (strtoupper(substr(trim($sql), 0, 6)) == 'INSERT') ? mysqli_stmt_insert_id($stmt) : mysqli_stmt_affected_rows($stmt);
}

function db_escape_like($like)
{
    return strtr($like, ['%' => '\%', '_' => '\_', '\\' => '\\\\']);
}
