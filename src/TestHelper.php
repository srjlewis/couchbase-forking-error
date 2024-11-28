<?php

namespace srjlewis\couchbaseForkingError;

use League\CLImate\CLImate;
use Phpfastcache\Util\SapiDetector;

class TestHelper extends \Phpfastcache\Tests\Helper\TestHelper
{
    public function __construct(string $testName)
    {
        $this->timestamp = microtime(true);
        $this->testName = $testName;
        $this->climate = new CLImate();
        $this->climate->forceAnsiOn();

        $this->printHeaders();
    }

    public function printHeaders(): void
    {
        if (SapiDetector::isWebScript() && !headers_sent()) {
            header('Content-Type: text/plain, true');
        }

        $loadedExtensions = get_loaded_extensions();
        natcasesort($loadedExtensions);
        $this->printText("[<blue>Begin Test:</blue> <magenta>$this->testName</magenta>]");
        $this->printText('[<blue>PHP</blue> <yellow>v' . PHP_VERSION . '</yellow> with: <green>' . implode(', ', $loadedExtensions) . '</green>]');
        $this->printText('---');
    }

    public function terminateTest(): void
    {
        $execTime = round(microtime(true) - $this->timestamp, 3);
        $totalCount = $this->numOfFailedTests + $this->numOfSkippedTests + $this->numOfPassedTests;

        $this->printText(
            \sprintf(
                '<blue>Test results:</blue><%1$s> %2$s %3$s failed</%1$s>, <%4$s>%5$s %6$s skipped</%4$s> and <%7$s>%8$s %9$s passed</%7$s> out of a total of %10$s %11$s.',
                $this->numOfFailedTests ? 'red' : 'green',
                $this->numOfFailedTests,
                ngettext('assertion', 'assertions', $this->numOfFailedTests),
                $this->numOfSkippedTests ? 'yellow' : 'green',
                $this->numOfSkippedTests,
                ngettext('assertion', 'assertions', $this->numOfSkippedTests),
                !$this->numOfPassedTests && $totalCount ? 'red' : 'green',
                $this->numOfPassedTests,
                ngettext('assertion', 'assertions', $this->numOfPassedTests),
                "<cyan>$totalCount</cyan>",
                ngettext('assertion', 'assertions', $totalCount),
            )
        );
        $this->printText('<blue>Test duration: </blue><yellow>' . $execTime . 's</yellow>');
        $this->printText('<blue>Test memory: </blue><yellow>' . $this->getReadableSize(memory_get_peak_usage()) . '</yellow>');

        // if ($this->numOfFailedTests) {
        //     exit(1);
        // }
        //
        // if ($this->numOfSkippedTests) {
        //     exit($this->numOfPassedTests ? 0 : 2);
        // }

        exit(0);
    }
}