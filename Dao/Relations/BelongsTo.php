<?php

namespace Libra\Dbal\Dao\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloBelongsTo;

class BelongsTo extends EloBelongsTo
{
    public function addConstraints()
    {
        if (static::$constraints) {
            // @override Mongodb 字段，不能有表名
            $this->query->where($this->ownerKey, '=', $this->child->{$this->foreignKey});
        }
    }

    public function addEagerConstraints(array $models)
    {
        // @override Mongodb 字段，不能有表名
        $this->query->whereIn($this->ownerKey, $this->getEagerModelKeys($models));
    }

    // 兼容 id 是 数组的情况
    protected function getEagerModelKeys(array $models)
    {
        $keys = [];

        // First we need to gather all of the keys from the parent models so we know what
        // to query for via the eager loading query. We will add them to an array then
        // execute a "where in" statement to gather up all of those related records.
        foreach ($models as $model) {
            if (!is_null($value = $model->{$this->foreignKey})) {
                // 兼容 id 是 数组的情况
                if (is_array($value)) {
                    $keys = array_merge($keys, $value);
                } else {
                    $keys[] = $value;
                }
            }
        }

        sort($keys);

        return array_values(array_unique($keys));
    }

    // 兼容 id 是 数组的情况
    public function match(array $models, Collection $results, $relation)
    {
        $foreign = $this->foreignKey;

        $owner = $this->ownerKey;

        // First we will get to build a dictionary of the child models by their primary
        // key of the relationship, then we can easily match the children back onto
        // the parents using that dictionary and the primary key of the children.
        $dictionary = [];

        foreach ($results as $result) {
            $attribute = $this->getDictionaryKey($result->getAttribute($owner));

            $dictionary[$attribute] = $result;
        }

        // Once we have the dictionary constructed, we can loop through all the parents
        // and match back onto their children using these keys of the dictionary and
        // the primary key of the children to map them onto the correct instances.
        foreach ($models as $model) {
            $mapKeys = $model->{$foreign};
            if (is_array($mapKeys)) {
                $attribute = array_map(function ($item) {
                    return $this->getDictionaryKey($item);
                }, $mapKeys);
                $mapValues = array_intersect_key($dictionary, array_flip($attribute));
                if ($mapValues) {
                    // @override relation 的值必须是 Arrayable
                    $model->setRelation($relation, $this->related->newCollection(array_values($mapValues)));
                }
            } else {
                $attribute = $this->getDictionaryKey($mapKeys);
                if (isset($dictionary[$attribute])) {
                    $model->setRelation($relation, $dictionary[$attribute]);
                }
            }
        }

        return $models;
    }
}
