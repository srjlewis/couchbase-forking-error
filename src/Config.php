<?php

namespace srjlewis\couchbaseForkingError;

class Config
{
    public function __construct(
        public string $username,
        public string $password,
        public array  $hosts,
        public string $bucket,
        public string $scope,
        public string $collection,
    ) {
    }
}