<?php
// autoload注册
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $file = __DIR__ . "/$class.php";
    if (file_exists($file)) {
        require_once $file;
    }
    $file = dirname(__DIR__,3) . "/$class.php";
    if (file_exists($file)) {
        require_once $file;
    }
});