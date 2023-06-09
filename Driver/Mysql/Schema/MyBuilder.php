<?php

namespace Libra\Dbal\Driver\Mysql\Schema;

use Closure;
use Illuminate\Database\Schema\MySqlBuilder;
use Libra\Dbal\Schema\Blueprint;

class MyBuilder extends MySQLBuilder
{
    /**
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param Closure|null $callback
     * @return Blueprint
     */
    protected function createBlueprint($table, ?Closure $callback = null): Blueprint
    {
        return new Blueprint($table, $callback);
    }
}
