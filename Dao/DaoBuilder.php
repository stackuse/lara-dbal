<?php

namespace Libra\Dbal\Dao;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Libra\Dbal\Dao\Concerns\QueryRelations;
use Libra\Dbal\Dao\Relations\BelongsTo;
use Libra\Dbal\Dao\Relations\HasMany;
use Libra\Dbal\Dao\Relations\HasOne;
use Libra\Dbal\Driver\Mongo\Query\MgQueryBuilder;
use RuntimeException;

class DaoBuilder extends Builder
{
    use QueryRelations;

    /**
     * The methods that should be returned from query builder.
     * @var array
     */
    protected array $mgPassthru = [
        'average',
        'avg',
        'count',
        'dd',
        'doesntExist',
        'dump',
        'exists',
        'getBindings',
        'getConnection',
        'getGrammar',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertUsing',
        'max',
        'min',
        'pluck',
        'pull',
        'push',
        'raw',
        'sum',
        'toSql',
    ];

    protected bool $isMongo = false;


    protected bool $hasRole = false;

    /**
     * @override 覆盖，兼容 mongo db
     * Create a new Eloquent query builder instance.
     *
     * @param MgQueryBuilder $query
     */
    public function __construct($query, $isMongo = false)
    {
        $this->isMongo = $isMongo;
        if ($this->isMongo) {
            $this->passthru = $this->mgPassthru;
        }
        parent::__construct($query);
    }


    /**
     * @override 使查询条件可以自定义
     *
     * @param mixed $id
     * @return $this
     */
    public function whereKey($id, $operator = '='): static
    {
        if (is_array($id) || $id instanceof Arrayable) {
            if ($this->model->getKeyType() === 'uuid') {
                array_walk($id, function (&$item) {
                    $item = $this->model->slug2uuid($item);
                });
            } elseif ($this->model->getKeyType() === 'string') {
                array_walk($id, function (&$item) {
                    $item = (string)$item;
                });
            }

            $this->query->whereIn($this->model->getKeyName(), $id);

            return $this;
        } else {
            if ($this->model->getKeyType() === 'uuid') {
                $id = $this->model->slug2uuid($id);
            } elseif ($this->model->getKeyType() === 'string') {
                $id = (string)$id;
            }
            return $this->where($this->model->getKeyName(), $operator, $id);
        }
    }

    /**
     * Add the "updated at" column to an array of values.
     * @param array $values
     * @return array
     */
    protected function addUpdatedAtColumn(array $values): array
    {
        if (!$this->model->usesTimestamps() || $this->model->getUpdatedAtColumn() === null) {
            return $values;
        }

        $column = $this->model->getUpdatedAtColumn();
        return array_merge(
            [$column => $this->model->freshTimestampString()],
            $values
        );
    }

    /**
     * @new 新增，方便根据 key 排序
     * @param string $direction
     * @return $this
     */
    public function orderByKey(string $direction = 'asc'): static
    {
        return $this->orderBy($this->defaultKeyName(), $direction);
    }


    public function setRole(bool $hasRole)
    {
        $this->hasRole = $hasRole;
    }

    /**
     * @new
     * @param int $roleId
     * @param int $roleType
     * @return $this
     */
    public function whereRole(int $roleId, int $roleType = 0): static
    {
        if ($roleType > 0) {
            return $this->where('role_id', '=', $roleId)->where('role_type', '=', $roleType);
        } else {
            return $this->where('user_id', '=', $roleId);
        }
    }

    /**
     * @new
     * @param int $morphId
     * @param string $morphType
     * @return $this
     */
    public function whereMorph(int $morphId, string $morphType = ''): static
    {
        return $this->where('morph_id', '=', $morphId)->where('morph_type', '=', $morphType);
    }

    /**
     * @new
     * @param $relationName
     * @param callable $callable
     * @return DaoBuilder
     */
    public function whereHasIn($relationName, callable $callable): DaoBuilder
    {
        $relationNames = explode('.', $relationName);
        $nextRelation = implode('.', array_slice($relationNames, 1));
        $method = $relationNames[0];
        $relation = Relation::noConstraints(function () use ($method) {
            return $this->$method();
        });
        /** @var Builder $in */
        if ($nextRelation) {
            $in = $relation->getQuery()->whereHasIn($nextRelation, $callable);
        } else {
            $in = $relation->getQuery()->where($callable);
        }

        if ($relation instanceof BelongsTo) {
            return $this->whereIn($relation->getForeignKey(), $in->select($relation->getOwnerKey()));
        }

        if ($relation instanceof HasOne) {
            return $this->whereIn($this->defaultKeyName(), $in->select($relation->getForeignKeyName()));
        }

        if ($relation instanceof HasMany) {
            return $this->whereIn($this->getKeyName(), $in->select($relation->getForeignKeyName()));
        }

        throw new RuntimeException(__METHOD__ . " 不支持 " . get_class($relation));
    }
}
