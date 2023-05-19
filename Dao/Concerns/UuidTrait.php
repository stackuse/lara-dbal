<?php

namespace Libra\Dbal\Dao\Concerns;

use Libra\Dbal\Dao\Casts\AsUuid;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Uuid;

trait UuidTrait
{

    /**
     * hook
     * @return void
     */
//    public static function bootUuidSlug()
//    {
//        // static::addGlobalScope(new SlugScope());
//    }

    public function initializeUuidTrait()
    {
        $this->casts[$this->getKeyName()] = AsUuid::class;
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model->getUuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'uuid';
    }

    public function getUuid(): Binary|string
    {
        if ($this->isMongo) {
            return new Binary(Uuid::v7()->toBinary(), Binary::TYPE_UUID);
        }
        return Uuid::v7()->toRfc4122();
    }

    /**
     * @param string $uuid
     * @return string
     */
    public function uuid2slug(string $uuid): string
    {
        $uuidObj = new Uuid($uuid);
        return $uuidObj->toBase58();
    }

    /**
     * @param string $slug
     * @return string
     */
    public function slug2uuid(string $slug): string
    {
        if ($this->isMongo) {
            return new Binary(Uuid::fromString($slug)->toBinary(), Binary::TYPE_UUID);
        }
        return Uuid::fromString($slug)->toRfc4122();
    }
}
