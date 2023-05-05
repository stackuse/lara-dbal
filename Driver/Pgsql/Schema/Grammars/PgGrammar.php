<?php

namespace Libra\Dbal\Driver\Pgsql\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Fluent;

class PgGrammar extends PostgresGrammar
{
    /**
     * @new
     * Create the column definition for a json type.
     *
     * @param Fluent $column
     * @return string
     */
    protected function typeArray(Fluent $column): string
    {
        return $column->arrayType . ' ARRAY';
    }
}
