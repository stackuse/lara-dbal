<?php

namespace Libra\Dbal\Driver\Mongo\Schema;

use Closure;
use Illuminate\Database\Schema\Builder;

class MgBuilder extends Builder
{
    /**
     * @override mongodb 没有判断 fields 的概念
     */
    public function hasColumn($table, $column): bool
    {
        return true;
    }

    /**
     * @override mongodb 没有判断 fields 的概念
     */
    public function hasColumns($table, array $columns): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasTable($collection)
    {
        return $this->hasCollection($collection);
    }

    /**
     * Determine if the given collection exists.
     * @param string $name
     * @return bool
     */
    public function hasCollection($name)
    {
        $db = $this->connection->getMongoDB();

        $collections = iterator_to_array($db->listCollectionNames([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return (bool)count($collections);
    }

    /**
     * @inheritdoc
     */
    public function table($collection, Closure $callback)
    {
        return $this->collection($collection, $callback);
    }

    /**
     * Modify a collection on the schema.
     * @param string $collection
     * @param Closure $callback
     * @return bool
     */
    public function collection(string $collection, Closure $callback)
    {
        $blueprint = $this->createBlueprint($collection);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    protected function createBlueprint($collection, Closure $callback = null)
    {
        return new MgBlueprint($this->connection, $collection);
    }

    /**
     * @inheritdoc
     */
    public function create($collection, Closure $callback = null, array $options = [])
    {
        $blueprint = $this->createBlueprint($collection);

        $blueprint->create($options);

        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * @inheritdoc
     */
    public function dropIfExists($collection)
    {
        if ($this->hasCollection($collection)) {
            return $this->drop($collection);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function drop($collection)
    {
        $blueprint = $this->createBlueprint($collection);

        return $blueprint->drop();
    }

    /**
     * @inheritdoc
     */
    public function dropAllTables()
    {
        foreach ($this->getAllCollections() as $collection) {
            $this->drop($collection);
        }
    }

    /**
     * Get all of the collections names for the database.
     * @return array
     */
    protected function getAllCollections()
    {
        $collections = [];
        foreach ($this->connection->getMongoDB()->listCollections() as $collection) {
            $collections[] = $collection->getName();
        }

        return $collections;
    }

    /**
     * @param string $name
     * @return bool|\MongoDB\Model\CollectionInfo
     * @todo 和 hasCollection 重复
     * Get collection.
     */
    public function getCollection($name)
    {
        $db = $this->connection->getMongoDB();

        $collections = iterator_to_array($db->listCollections([
            'filter' => [
                'name' => $name,
            ],
        ]), false);

        return count($collections) ? current($collections) : false;
    }
}
