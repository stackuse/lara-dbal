<?php

namespace Libra\Dbal\Driver\Mongo;

use Closure;
use Libra\Dbal\Driver\Mongo\Query\MgQueryBuilder;
use Libra\Dbal\Driver\Mongo\Query\MgQueryGrammar;
use Libra\Dbal\Driver\Mongo\Query\MgQueryProcessor;
use Libra\Dbal\Driver\Mongo\Schema\MgBuilder;
use Libra\Dbal\Driver\Mongo\Schema\MgGrammar;
use Illuminate\Database\Connection;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Driver\Session;

class MgConnection extends Connection
{
    /**
     * The MongoDB database handler.
     * @var Database
     */
    protected Database $db;

    /**
     * The MongoDB connection handler.
     * @var Client
     */
    protected Client $connection;

    protected ?Session $session = null;

    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);

        $this->connection = $this->getClient();
        $this->db = $this->connection->selectDatabase($database);
    }

    /**
     * @new same as getPdo()
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    public function query()
    {
        return new MgQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * @inheritdoc
     */
    public function getElapsedTime($start): float
    {
        return parent::getElapsedTime($start);
    }

    /**
     * Begin a fluent query against a database collection.
     * @param string $table
     * @param null $as
     * @return MgQueryBuilder
     */
    public function table($table, $as = null): MgQueryBuilder
    {
        return $this->collection($table);
    }

    /**
     * Begin a fluent query against a database collection.
     * @param string $collection
     * @return MgQueryBuilder
     */
    public function collection(string $collection): MgQueryBuilder
    {
        $query = new MgQueryBuilder($this);

        return $query->from($collection);
    }

    /**
     * Get a MongoDB collection. 需要包一下，记录日志
     * @param string $name
     * @return MgCollection
     */
    public function getCollection(string $name): MgCollection
    {
        return new MgCollection($this, $this->db->selectCollection($name));
    }

    public function getSchemaBuilder()
    {
        return new MgBuilder($this);
    }

    /**
     * Get the MongoDB database object.
     * @return Database
     */
    public function getMongoDB(): Database
    {
        return $this->db;
    }

    public function beginTransaction(array $options = [])
    {
        // parent::beginTransaction();
        if (!$this->getSession()) {
            $this->session = $this->connection->startSession();
            $this->session->startTransaction($options);
        }
    }

    public function getSession()
    {
        return $this->session;
    }

    public function commit()
    {
        if ($this->getSession()) {
            $this->session->commitTransaction();
            $this->clearSession();
        }
    }

    protected function clearSession()
    {
        $this->session = null;
    }

    public function rollBack($toLevel = null)
    {
        if ($this->getSession()) {
            $this->session->abortTransaction();
            $this->clearSession();
        }
    }

    /**
     * Dynamically pass methods to the connection.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->db, $method], $parameters);
    }

    protected function getDefaultPostProcessor(): MgQueryProcessor
    {
        return new MgQueryProcessor();
    }

    protected function getDefaultQueryGrammar(): MgQueryGrammar
    {
        return new MgQueryGrammar();
    }

    protected function getDefaultSchemaGrammar(): MgGrammar
    {
        return new MgGrammar();
    }
}
