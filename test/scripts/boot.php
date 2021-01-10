<?php

$composerAutoload = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (!file_exists($composerAutoload)) {
	exit('Run composer install first.' . PHP_EOL);
}

require_once $composerAutoload;

if (!defined('LAYERCACHE_TEST_TMP_DIR')) {
	define('LAYERCACHE_TEST_TMP_DIR', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'tmp');
}

if (!is_dir(LAYERCACHE_TEST_TMP_DIR)) {
	mkdir(LAYERCACHE_TEST_TMP_DIR, 0777);
}

error_reporting(E_ALL & ~E_DEPRECATED);
