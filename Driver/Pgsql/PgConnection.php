<?php

namespace Libra\Dbal\Driver\Pgsql;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Libra\Dbal\Driver\Pgsql\Schema\Grammars\PgGrammar;
use Libra\Dbal\Driver\Pgsql\Schema\PgBuilder;
use Illuminate\Database\PostgresConnection;

class PgConnection extends PostgresConnection
{

    /**
     * @override
     * @return QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * @override
     * @return PgBuilder
     */
    public function getSchemaBuilder(): PgBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new PgBuilder($this);
    }

    /**
     * @override
     * Get the default schema grammar instance.
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new PgGrammar());
    }
}
