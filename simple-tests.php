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

$username         = $config['username'];
$password         = $config['password'];
$connectionString = 'couchbase://' . implode(',', $config['hosts']);
$bucketName       = $config['bucket'];
$scopeName        = $config['scope'];
$collectionName   = $config['collection'];

use \Couchbase\ClusterOptions;
use \Couchbase\Cluster;
use \Couchbase\ForkEvent;

$clusterOpts = new ClusterOptions();
$clusterOpts->credentials($username, $password);

$couchbase  = new Cluster($connectionString, $clusterOpts);
$bucket     = $couchbase->bucket($bucketName);
$scope      = $bucket->scope($scopeName);
$collection = $scope->collection($collectionName);

$value = \random_int(1, 254);

$collection->upsert('setSuccessKey', $value);
$data = $collection->get('setSuccessKey')->content();

$exit    = 0;
$failed  = 0;
$success = 0;

if ($collection->get('setSuccessKey')->content() === $value) {
    echo "[SUCCESS] Set and return key success\r\n";
    $success++;
} else {
    echo "[FAIL] Set and return key fail\r\n";
    $exit = 1;
    $failed++;
}

foreach (range(1, 100) as $i) {
    $value = \random_int(1, 254);
    try {
        $collection->upsert('forkSuccessTestKey - ' . $i, $value);
    } catch (\Couchbase\Exception\TimeoutException $e) {
        echo "[FAIL] Parent Upsert failed- {$e->getMessage()}\r\n";
        $exit = 1;
        $failed++;
        break;
    } catch (\Couchbase\Exception\CouchbaseException $e) {
        echo "[FAIL] Parent Upsert failed - {$e->getMessage()}\r\n";
        $exit = 1;
        $failed++;
        break;
    }

    Cluster::notifyFork(ForkEvent::PREPARE);
    $pid = \pcntl_fork();

    $status = 0;

    if ($pid == -1) {
        $this->helper->assertFail('Unable to fork');
    } elseif ($pid) {
        Cluster::notifyFork(ForkEvent::PARENT);
        $continue = true;
        $start    = microtime(true);
        while ($continue) {
            if (\pcntl_wait($status, WNOHANG)) {
                $continue = false;
            } elseif (microtime(true) - $start > 10) {
                if (\posix_kill($pid, 0)) {
                    echo "[DEBUG] Killing pid - $pid\r\n";
                    \posix_kill($pid, SIGKILL);
                }
                usleep(50);
            }
        }
    } else {
        Cluster::notifyFork(ForkEvent::CHILD);
        exit((int)$collection->get('forkSuccessTestKey - ' . $i)->content());
    }

    $childReturned = sprintf("Child %s returned %s", str_pad($pid, 5), str_pad(\pcntl_wexitstatus($status), 3));

    if ($value === \pcntl_wexitstatus($status)) {
        echo "[SUCCESS] {$childReturned}: The fork was a success and returned correctly\r\n";
        $success++;
    } else {
        echo "[FAIL] {$childReturned}: The child fork failed to return\r\n";
        $exit = 1;
        $failed++;
    }
}

echo "Success: $success\r\n";
echo "Failed: $failed\r\n";

exit($exit);