<?php

namespace Libra\Dbal\Driver\Mysql;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Libra\Dbal\Driver\Mysql\Schema\MyBuilder;
use Illuminate\Database\MySqlConnection;

class MyConnection extends MySqlConnection
{
    public function getSchemaBuilder(): MyBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new MyBuilder($this);
    }

    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }
}
