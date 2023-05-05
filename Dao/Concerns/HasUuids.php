<?php

namespace Libra\Dbal\Dao\Concerns;

use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Uuid;

trait HasUuids
{
    protected bool $useUuId = false;
    /**
     * Initialize the trait.
     *
     * @return void
     */
    public function initializeHasUuids()
    {
        $this->usesUniqueIds = true;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        if ($this->useUuId) {
            return [$this->getKeyName()];
        } else {
            return [];
        }
    }

    /**
     * Generate a new UUID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return $this->getUuid();
    }


    public function getUuid(): Binary|string
    {
        if ($this->isMongo) {
            return new Binary(Uuid::v7()->toBinary(), Binary::TYPE_UUID);
        }
        return Uuid::v7()->toRfc4122();
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return 'uuid';
        }

        return $this->keyType;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        if (in_array($this->getKeyName(), $this->uniqueIds())) {
            return false;
        }

        return $this->incrementing;
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
