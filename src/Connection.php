<?php

namespace srjlewis\couchbaseForkingError;

use Couchbase\Bucket;
use Couchbase\BucketInterface;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Collection;
use Couchbase\CollectionInterface;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Scope;
use Couchbase\ScopeInterface;
use Couchbase\UpsertOptions;

class Connection
{

    protected Cluster                        $couchbase;
    protected BucketInterface|Bucket         $bucket;
    protected ScopeInterface|Scope           $scope;
    protected CollectionInterface|Collection $collection;

    public function __construct(
        Config $config,
        ?string $username = null,
        ?string $password = null,
        ?array $hosts = null,
        ?string $bucket = null,
        ?string $scope = null,
        ?string $collection = null,
    ) {
        $hosts = $hosts ?? $config->hosts;

        $opts = new ClusterOptions();
        $opts->credentials(
            $username ?? $config->username,
            $password ?? $config->password,
        );

        $connectionString = 'couchbase://' . implode(',', $hosts);

        $this->couchbase  = new Cluster($connectionString, $opts);
        $this->bucket     = $this->couchbase->bucket($bucket ?? $config->bucket);
        $this->scope      = $this->bucket->scope($scope ?? $config->scope);
        $this->collection = $this->scope->collection($collection ?? $config->collection);
    }

    public function set(string $id, mixed $data, ?int $ttl = null): bool
    {
        $opts = (new UpsertOptions())->expiry($ttl ?? 3600);
        try {
            $this->collection->upsert($id, serialize($data), $opts);
            return true;
        } catch (CouchbaseException) {
            return false;
        }
    }

    public function get(string $id): mixed
    {
        try {
            $document = $this->collection->get($id);
            if(!$document->error()) {
                return unserialize($document->content());
            }
        } catch (DocumentNotFoundException) {
        }
        return null;
    }
}