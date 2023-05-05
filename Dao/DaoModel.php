<?php

namespace Libra\Dbal\Dao;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Libra\Dbal\Dao\Concerns\HasUuids;
use Libra\Dbal\Dao\Concerns\ModelRelations;
use Libra\Dbal\Dao\Traits\MongoTrait;
use MongoDB\BSON\UTCDateTime;

class DaoModel extends Model
{
    // 不要用 SoftDeletes，用 status 替代
    // use SoftDeletes;
    use ModelRelations;
    use HasUuids;
    use MongoTrait;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    // 将 id 转换成 slug
    protected bool $id2slug = false;

    /**
     * @override
     * @modified 增加 appends 和 hidden
     * Create a new instance of the given model.
     *
     * @param array $attributes
     * @param bool $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);

        // @notice 增加 appends 和 hidden
        $model->append($this->getAppends());

        $model->makeHidden($this->getHidden());

        return $model;
    }

    /**
     * @override 重写兼容 mongodb
     * Create a new Eloquent query builder for the model.
     *
     * @param Builder $query
     * @return DaoBuilder
     */
    public function newEloquentBuilder($query)
    {
        // parent::newEloquentBuilder($query)
        return new DaoBuilder($query, $this->isMongo);
    }

    /**
     * @new 获取原始 id 的值
     * @return mixed
     * 类似函数 getKeyForSaveQuery，getKey
     */
    public function getKeyRaw(): mixed
    {
        return $this->attributes[$this->getKeyName()];
    }

    /**
     * @override
     */
    protected function asDateTime($value)
    {
        // Convert UTCDateTime instances.
        if ($value instanceof UTCDateTime) {
            $date = $value->toDateTime();

            $seconds = $date->format('U');
            $milliseconds = abs($date->format('v'));
            $timestampMs = sprintf('%d%03d', $seconds, $milliseconds);

            return Date::createFromTimestampMs($timestampMs);
        }

        return parent::asDateTime($value);
    }

    /**
     * @override 重写 query方法，增加参数和返回类型
     * Begin querying the model.
     * @param bool $id2slug 是否加密 ID
     * @return DaoBuilder
     */
    public static function query(bool $id2slug = false)
    {
        // parent::query();
        $model = new static;
        // id 转化成 slug
        $model->id2slug = $id2slug;
        return $model->newQuery();
    }
}
