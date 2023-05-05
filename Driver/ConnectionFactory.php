<?php

namespace Libra\Dbal\Driver;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use Libra\Dbal\Driver\Mongo\MgConnection;
use Libra\Dbal\Driver\Mongo\MgConnector;
use Libra\Dbal\Driver\Mysql\MyConnection;
use Libra\Dbal\Driver\Pgsql\PgConnection;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use InvalidArgumentException;
class ConnectionFactory extends BaseConnectionFactory
{
    /**
     * Create a connector instance based on the configuration.
     *
     * @param  array  $config
     * @return ConnectorInterface
     *
     * @throws InvalidArgumentException|BindingResolutionException
     */
    public function createConnector(array $config)
    {
        if (! isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        return match ($config['driver']) {
            'mysql' => new MySqlConnector,
            'pgsql' => new PostgresConnector,
            'mongo' => new MgConnector,
            default => throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]."),
        };
    }


    /**
     * @override
     * Create a new connection instance.
     *
     * @param string $driver
     * @param \PDO|Closure $connection
     * @param string $database
     * @param string $prefix
     * @param array $config
     * @return Connection
     *
     * @throws InvalidArgumentException
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }
        return match ($driver) {
            'mysql' => new MyConnection($connection, $database, $prefix, $config),
            'pgsql' => new PgConnection($connection, $database, $prefix, $config),
            'mongo' => new MgConnection($connection, $database, $prefix, $config),
            default => throw new InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }
}
