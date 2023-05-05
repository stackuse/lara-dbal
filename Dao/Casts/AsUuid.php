<?php

namespace Libra\Dbal\Dao\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsUuid implements CastsAttributes
{
    // @notice 目前没用
    public function serialize($model, string $key, $value, array $attributes)
    {
        return $model->uuid2slug($value);
    }
    public function get($model, string $key, $value, array $attributes)
    {
        return $model->uuid2slug($value);
    }
    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}
