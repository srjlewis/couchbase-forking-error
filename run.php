<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';

$configFileName = __DIR__ . '/config/' . ($argv[1] ?? 'github-actions') . '.php';
if (!\file_exists($configFileName)) {
    $configFileName = __DIR__ . '/configs/github-actions.php';
}

$config = (include $configFileName);

$config = new \srjlewis\couchbaseForkingError\Config(
    $config['username'],
    $config['password'],
    $config['hosts'],
    $config['bucket'],
    $config['scope'],
    $config['collection'],
);

(new \srjlewis\couchbaseForkingError\Tester($config));