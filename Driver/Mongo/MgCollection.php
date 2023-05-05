<?php

namespace Libra\Dbal\Driver\Mongo;

use Exception;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;

/**
 * 这个文件必须存在，便于记录日志
 */
class MgCollection
{
    /**
     * The connection instance.
     *
     * @var MgConnection
     */
    protected MgConnection $connection;

    /**
     * The MongoCollection instance.
     *
     * @var Collection
     */
    protected Collection $collection;

    /**
     * @param MgConnection $connection
     * @param Collection $collection
     */
    public function __construct(MgConnection $connection, Collection $collection)
    {
        $this->connection = $connection;
        $this->collection = $collection;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        $start = microtime(true);
        $result = call_user_func_array([$this->collection, $method], $parameters);

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->connection->getElapsedTime($start);

        $query = [];

        // Convert the query parameters to a json string.
        foreach ($parameters as $parameter) {
            try {
                if ($parameter instanceof ObjectID) {
                    $query[] = (string)$parameter;
                } else {
                    $query[] = json_encode($parameter);
                }
            } catch (Exception $e) {
                $query[] = '{...}';
            }
        }

        $queryString = $this->collection->getCollectionName() . '.' . $method . '(' . implode(',', $query) . ')';

        $this->connection->logQuery($queryString, [], $time);

        return $result;
    }
}
