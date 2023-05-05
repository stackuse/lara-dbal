<?php

namespace Libra\Dbal\Dao\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany as EloMorphMany;

class MorphMany extends EloMorphMany
{
    /**
     * Get the name of the "where in" method for eager loading.
     *
     * @param Model $model
     * @param string $key
     *
     * @return string
     */
    protected function whereInMethod(Model $model, $key): string
    {
        return 'whereIn';
    }
}
