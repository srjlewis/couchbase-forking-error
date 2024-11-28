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
}