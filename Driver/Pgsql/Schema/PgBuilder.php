<?php

namespace Libra\Dbal\Driver\Pgsql\Schema;

use Closure;
use Illuminate\Database\Schema\PostgresBuilder;
use Libra\Dbal\Schema\Blueprint;

class PgBuilder extends PostgresBuilder
{
    /**
     * @override
     * Create a new command set with a Closure.
     *
     * @param string $table
     * @param Closure|null $callback
     * @return Blueprint
     */
    public function createBlueprint($table, ?Closure $callback = null): Blueprint
    {
        return new Blueprint($table, $callback);
    }

    /**
     * @new
     * Enable foreign key constraints.
     *
     * @return bool
     */
    public function enablePostgis(): bool
    {
        return $this->connection->statement(
            $this->grammar->compileEnablePostgis()
        );
    }

    /**
     * @new
     * Disable foreign key constraints.
     *
     * @return bool
     */
    public function disablePostgis(): bool
    {
        return $this->connection->statement(
            $this->grammar->compileDisablePostgis()
        );
    }
}
