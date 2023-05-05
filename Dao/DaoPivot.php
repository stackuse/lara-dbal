<?php

namespace Libra\Dbal\Dao;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class DaoPivot extends DaoModel
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
