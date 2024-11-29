<?php

use srjlewis\couchbaseForkingError\Config;

return [
    'username'   => 'test',
    'password'   => 'password',
    'hosts'      => ['127.0.0.1'],
    'bucket'     => 'test',
    'scope'      => '_default',
    'collection' => '_default'
];

return new Config(
    'test',
    'password',
    ['127.0.0.1'],
    'test',
    '_default',
    '_default'
);