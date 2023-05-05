<?php

namespace Libra\Dbal\Dao\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MongoDB\BSON\ObjectId;

class AsRawKey implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if (!$value) {
            return $value;
        }
        if ($model->isMongo) {
            if (is_array($value)) {
                $rawValues = [];
                foreach ($value as $item) {
                    $rawValues[] = new ObjectId($item);
                }
                return [$key => $rawValues];
            } else {
                return [$key => new ObjectId($value)];
            }
        }
    }
}
