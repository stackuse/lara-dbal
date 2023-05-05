<?php

namespace Libra\Dbal\Dao\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloBelongsToMany;

/**
 * @todo 换成分别查询两个表，不联表查询
 */
class BelongsToMany extends EloBelongsToMany
{
    protected array $keys = [];

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param array $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        $this->keys = $this->getKeys($models, $this->parentKey);
        //        $whereIn = $this->whereInMethod($this->parent, $this->parentKey);
        //
        //        $this->query->{$whereIn}(
        //            $this->getQualifiedForeignPivotKeyName(),
        //            $this->keys
        //        );
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        // @todo 不要连表查询
        // $this->performJoin();

        if (static::$constraints) {
            $this->addWhereConstraints();
        }
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function addWhereConstraints()
    {
        $this->query->where(
            $this->getQualifiedForeignPivotKeyName(), '=', $this->parent->{$this->parentKey}
        );

        return $this;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate our pivot
        // models with the result of those columns as a separate model relation.
        $query = $this->newPivotStatement();

        foreach ($this->pivotWheres as $arguments) {
            $query->where(...$arguments);
        }

        foreach ($this->pivotWhereIns as $arguments) {
            $query->whereIn(...$arguments);
        }

        foreach ($this->pivotWhereNulls as $arguments) {
            $query->whereNull(...$arguments);
        }
        // @todo mongodb 的 keys不对，需要转化成字符串
        $pivots = $query->whereIn($this->foreignPivotKey, $this->keys)->get();

        $relatedKeys = $pivots->map(function ($value) {
            return $value->{$this->relatedPivotKey};
        })->values()->unique(null, true)->sort()->all();

        $builder = $this->query->applyScopes();

        // @todo 优化sql
        $models = $builder->getQuery()->whereIn($this->relatedKey, $relatedKeys)->get();

        // @todo 完善
        foreach ($pivots as $k => $v) {
            $pivots[$k]->{$this->accessor} = $models->where($this->relatedKey, $v->{$this->relatedPivotKey})->first();
        }

        return $this->related->newCollection($pivots->toArray());
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation): array
    {
        // @todo $results 变成了array
        $dictionary = [];
        foreach ($results as $result) {
            if ($result->{$this->accessor}) {
                $relate = $result->{$this->accessor};
                unset($result->{$this->accessor});
                $relate->{$this->accessor} = $result;
                $dictionary[$relate->{$this->accessor}->{$this->foreignPivotKey}][] = $relate;
            }
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->parentKey);
            if (isset($dictionary[$key])) {
                $model->setRelation(
                    $relation, $this->related->newCollection($dictionary[$key])
                );
            }
        }

        return $models;
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param array $models
     * @param string $relation
     * @return array
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
     * Get the select columns for the relation query.
     *
     * @param array $columns
     * @return array
     */
    protected function shouldSelect(array $columns = ['*']): array
    {
        if ($columns == ['*']) {
            $columns = [$this->related->getTable() . '.*'];
        }
        return $columns;

        //        return array_merge($columns, $this->aliasedPivotColumns());
    }
}
