<?php

namespace Libra\Dbal\Dao\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo as EloMorphTo;

class MorphTo extends EloMorphTo
{
    /**
     * @inheritdoc
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $this->query->where($this->getOwnerKeyName(), '=', $this->parent->{$this->foreignKey});
        }
    }

    /**
     * @inheritdoc
     */
    protected function getResultsByType($type)
    {
        $instance = $this->createModelByType($type);

        $key = $instance->getKeyName();

        $query = $instance->newQuery();

        return $query->whereIn($key, $this->gatherKeysByType($type, $instance->getKeyType()))->get();
    }

    /**
     * Get the name of the "where in" method for eager loading.
     * @param Model $model
     * @param string $key
     * @return string
     */
    protected function whereInMethod(Model $model, $key): string
    {
        return 'whereIn';
    }
}
