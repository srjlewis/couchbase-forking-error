<?php
$pid = (int)$argv[1] ?? 0;
\usleep(500);
//sleep(10);
if (\posix_kill($pid, 0)) {
    \posix_kill($pid, SIGKILL);
}