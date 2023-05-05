<?php

namespace Libra\Dbal\Providers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseServiceProvider as BaseServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
use Libra\Dbal\Driver\ConnectionFactory;

class DatabaseServiceProvider extends BaseServiceProvider
{
    /**
     * @override 扩展相关数据库操作
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices(): void
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });

        $this->app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager;
        });
    }
}
