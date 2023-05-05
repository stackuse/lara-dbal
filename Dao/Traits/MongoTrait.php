<?php

namespace Libra\Dbal\Dao\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Uid\Uuid;

trait MongoTrait
{
    public bool $isMongo = false;
    public function initializeMongoTrait(): void
    {
        $this->isMongo = $this->getConnection()->getDriverName() === 'mongo';
        if ($this->isMongo) {
            $this->setKeyName('_id');
            $this->setKeyType('string');
            // 不能添加这个，不然不会返回 _id
            // $this->setIncrementing(false);
        }
    }

    /**
     * @override
     * Encode the given value as JSON.
     *
     * @param mixed $value
     * @return string
     */
    protected function asJson($value)
    {
        if ($this->isMongo) {
            return $value;
        }
        parent::asJson($value);
    }

    /**
     * @override
     * Decode the given JSON back into an array or object.
     *
     * @param string $value
     * @param bool $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        if ($this->isMongo) {
            return $value;
        }
        parent::fromJson($value, $asObject);
    }

    /**
     * @override mongodb 不能制定表名
     * @param string $column
     * @return string
     * Qualify the given column name by the model's table.
     *
     * @notice  join的估计有问题，直接返回表名
     */
    public function qualifyColumn($column): string
    {
        if ($this->isMongo) {
            return $column;
        }
        return parent::qualifyColumn($column);
    }

    /**
     * @override mongodb 的 为 _id
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this)) . '_id';
    }

    /**
     * Get a fresh timestamp for the model.
     *
     */
    public function freshTimestamp()
    {
        if ($this->isMongo) {
            return new UTCDateTime(Date::now()->format('Uv'));
        }
        return Date::now();
    }

    /**
     * @override
     *  适配mongodb
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        if ($this->isMongo) {
            $attributes = $this->convertObjectId($attributes, );
        }

        // Convert dot-notation dates.
        foreach ($this->getDates() as $key) {
            if (Str::contains($key, '.') && Arr::has($attributes, $key)) {
                Arr::set($attributes, $key, (string)$this->asDateTime(Arr::get($attributes, $key)));
            }
        }

        return $attributes;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function convertObjectId(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if ($key === $this->getKeyName()) {
                $key = 'id';
                // 删除 _id
                unset($attributes[$this->getKeyName()]);
                $attributes[$key] = $value;
            }
            if ($value instanceof ObjectID) {
                $attributes[$key] = (string)$value;
            } elseif ($value instanceof Binary) {
                if ($value->getType() === Binary::TYPE_UUID) {
                    $attributes[$key] = Uuid::fromBinary($value->getData())->toBase58();
                } else {
                    $attributes[$key] = $value->getData();
                }
            } elseif (is_array($value)) {
                $attributes[$key] = $this->convertObjectId($value);
            }
        }
        return $attributes;
    }

    /**
     * 在 toArray 的时候会用到，关联查询时用不到
     * 目前在 分页 获取 id 时 会单独用到，其他地方没有
     * @param mixed|null $value
     * @return mixed
     */
    public function getIdAttribute(mixed $value = null): mixed
    {
        // 将 mongo 没有 id，需要将 _id 替代 id
        if ($this->isMongo && !empty($this->attributes[$this->getKeyName()])) {
            $value = $this->attributes[$this->getKeyName()];
            if ($value instanceof ObjectID) {
                $value = (string)$value;
            } elseif ($value instanceof Binary) {
                if ($value->getType() === Binary::TYPE_UUID) {
                    $value = Uuid::fromBinary($value->getData())->toBase58();
                } else {
                    $value = $value->getData();
                }
            }
        }
        // @todo
        if ($this->id2slug) {

        }

        return $value;
    }
}
