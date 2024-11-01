<?php

defined('ABSPATH') || exit;
if (!defined('TARGET_LOG_DIR')) {
    define('TARGET_LOG_DIR', WP_CONTENT_DIR . '/logs/target/');
}

function target_log_file($msg, $name = '')
{

    $trace = debug_backtrace();

    $name = ('' == $name) ? $trace[1]['function'] : $name;

    $error_dir = TARGET_LOG_DIR . '/' . date('Y-m') . '.txt';
    $time = date('d-m-Y H:i:s ');
    $msg = print_r($msg, true);
    $text = $time . ' - ' . $name . "  |  " . $msg;
    @file_put_contents($error_dir, $text . "\n", FILE_APPEND | FILE_TEXT);

}
