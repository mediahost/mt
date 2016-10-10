<?php

$allowedIPs = [
    '94.113.177.5', // Petr - Brno
    '37.221.251.252', // Petr - SnS
    '188.121.172.183', // Samo
    '127.0.0.1',
    '::1',
    // TMPs
    '149.62.146.153', // Brno TMP1
    '94.113.216.110', // Brno TMP2
];

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) || !isset($_SERVER['REMOTE_ADDR']) ||
        !in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Adminer is available only from localhost (' . $_SERVER['REMOTE_ADDR'] . ')';
    for ($i = 2e3; $i; $i--)
        echo substr(" \t\r\n", rand(0, 3), 1);
    exit;
}


$root = __DIR__ . '/../../vendor/dg/adminer-custom';

if (!is_file($root . '/index.php')) {
    echo "Install Adminer using `composer update`\n";
    exit(1);
}


if (isset($_GET['file']) && preg_match('#^(?:static/)?[\w.-]+\.(\w+)$#', $_GET['file'], $m) && is_file("$root/$_GET[file]")) {
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }

    header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('1 month')) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    if ($m[1] === 'css') {
        header('Content-Type: text/css; charset=utf-8');
    } elseif ($m[1] === 'js') {
        header('Content-Type: text/javascript; charset=utf-8');
    } elseif ($m[1] === 'gif' || $m[1] === 'png' || $m[1] === 'jpg') {
        header("Content-Type: image/$m[1]");
    }
    readfile("$root/$_GET[file]");
    exit;
}


require $root . '/index.php';
