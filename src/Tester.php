<?php

namespace srjlewis\couchbaseForkingError;

use Couchbase\Cluster;
use Couchbase\ForkEvent;

class Tester
{
    protected TestHelper $helper;

    public function __construct(Config $config)
    {
        $extensionVersion = \phpversion('couchbase');
        $this->helper     = new TestHelper('Extension Version ' . $extensionVersion);

        $this->helper->printInfoText('Running forking failure process test');
        $cache = new Connection($config);

        $value = \random_int(1, 254);

        $cache->set('setSuccessKey', $value);
        if ($cache->get('setSuccessKey') === $value) {
            $this->helper->assertPass('Set and return key success');
        } else {
            $this->helper->assertFail('Set and return key fail');
        }

        $cache1 = new Connection($config);
        $cache2 = new Connection($config, 'test2');

        foreach (range(1, 100) as $i) {
            $this->testForking($cache1, $cache2, $i);
        }

        $this->helper->terminateTest();
    }


    protected function testForking(Connection $cache1, Connection $cache2, int $attempt): void
    {
        $value1 = \random_int(1, 125);
        $value2 = \random_int(1, 125);

        $cache1->set('forkSuccessTestKey1-' . $attempt, $value1);
        $cache2->set('forkSuccessTestKey2-' . $attempt, $value2);

        Cluster::notifyFork(ForkEvent::PREPARE);
        $pid = \pcntl_fork();

        if ($pid == -1) {
            $this->helper->assertFail('Unable to fork');
        } elseif ($pid) {
            Cluster::notifyFork(ForkEvent::PARENT);
            $this->helper->runAsyncProcess('php "' . __DIR__ . '/../scripts/monitor_fork.php" ' . $pid);
            \pcntl_wait($status);
        } else {
            Cluster::notifyFork(ForkEvent::CHILD);
            exit($cache1->get('forkSuccessTestKey1-' . $attempt) + $cache2->get('forkSuccessTestKey2-' . $attempt));
        }

        $childReturned = 'Child returned <green>' . \pcntl_wexitstatus($status) . '</green>';

        if (($value1 + $value2) === \pcntl_wexitstatus($status)) {
            $this->helper->assertPass($childReturned . ': The fork was a success and returned correctly');
        } else {
            $this->helper->assertFail($childReturned . ': The child fork failed to return');
        }
    }
}