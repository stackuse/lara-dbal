<?php

namespace Libra\Dbal\Dao\Concerns;

use Libra\Dbal\Dao\Relations\BelongsTo;
use Libra\Dbal\Dao\Relations\BelongsToMany;
use Libra\Dbal\Dao\Relations\EmbedsMany;
use Libra\Dbal\Dao\Relations\EmbedsOne;
use Libra\Dbal\Dao\Relations\HasMany;
use Libra\Dbal\Dao\Relations\HasOne;
use Libra\Dbal\Dao\Relations\MorphMany;
use Libra\Dbal\Dao\Relations\MorphOne;
use Libra\Dbal\Dao\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * 重写 ModelRelations，兼容 mongodb
 */
trait ModelRelations
{
    /**
     * Define a one-to-one relationship.
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     * @param string $related
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null)
    {
        // Check if it is a relation with an original model.
        if (is_subclass_of($related, BaseModel::class)) {
            return parent::morphOne($related, $name, $type, $id, $localKey);
        }

        $instance = new $related;

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphOne($instance->newQuery(), $this, $type, $id, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $instance = $this->newRelatedInstance($related);
        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     * @param string $related
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $localKey
     * @return MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        // Check if it is a relation with an original model.
        if (is_subclass_of($related, BaseModel::class)) {
            return parent::morphMany($related, $name, $type, $id, $localKey);
        }

        $instance = new $related;

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany($instance->newQuery(), $this, $type, $id, $localKey);
    }

    /**
     * @done 目前没问题
     * Define an inverse one-to-one or many relationship.
     * @param string $related
     * @param null $foreignKey
     * @param null $ownerKey
     * @param null $relation
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null): BelongsTo
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_id';
        }

        // Once we have the foreign key names we'll just create a new Eloquent query
        // for the related models and return the relationship instance which will
        // actually be responsible for retrieving and hydrating every relation.
        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return new BelongsTo($instance->newQuery(), $this, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $ownerKey
     * @return MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if ($name === null) {
            [$current, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $name = Str::snake($caller['function']);
        }

        [$type, $id] = $this->getMorphs($name, $type, $id);

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. When that is the case we will pass in a dummy query as
        // there are multiple types in the morph and we can't use single queries.
        if (($class = $this->$type) === null) {
            return new MorphTo(
                $this->newQuery(), $this, $id, $ownerKey, $type, $name
            );
        }

        // If we are not eager loading the relationship we will essentially treat this
        // as a belongs-to style relationship since morph-to extends that class and
        // we will pass in the appropriate values so that it behaves as expected.
        $class = $this->getActualClassNameForMorph($class);

        $instance = new $class;

        $ownerKey = $ownerKey ?? $instance->getKeyName();

        return new MorphTo(
            $instance->newQuery(), $this, $id, $ownerKey, $type, $name
        );
    }

    /**
     * Define a many-to-many relationship.
     * @param string $related
     * @param string $collection
     * @param string $foreignKey
     * @param string $otherKey
     * @param string $parentKey
     * @param string $relatedKey
     * @param string $relation
     * @return BelongsToMany
     */
    public function belongsToMany(
        $related,
        $collection = null,
        $foreignKey = null,
        $otherKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null
    ): BelongsToMany
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if ($relation === null) {
            $relation = $this->guessBelongsToManyRelation();
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $instance = new $related;

        $otherKey = $otherKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if ($collection === null) {
            $collection = $instance->getTable();
        }

        // Now we're ready to create a new query builder for the related model and
        // the relationship instances for the relation. The relations will set
        // appropriate query constraint and entirely manages the hydrations.
        $query = $instance->newQuery();

        return new BelongsToMany(
            $query,
            $this,
            $collection,
            $foreignKey,
            $otherKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relation
        );
    }

    /**
     * Get the relationship name of the belongs to many.
     * @return string
     */
    protected function guessBelongsToManyRelation()
    {
        if (method_exists($this, 'getBelongsToManyCaller')) {
            return $this->getBelongsToManyCaller();
        }

        return parent::guessBelongsToManyRelation();
    }

    /**
     * Define an embedded one-to-many relationship.
     * @param string $related
     * @param string|null $localKey
     * @param string|null $foreignKey
     * @param string|null $relation
     * @return EmbedsMany
     */
    protected function embedsMany(string $related, string $localKey = null, string $foreignKey = null, string $relation = null): EmbedsOneOrManyMany
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if ($relation === null) {
            [, $caller] = debug_backtrace(false);

            $relation = $caller['function'];
        }

        if ($localKey === null) {
            $localKey = $relation;
        }

        if ($foreignKey === null) {
            $foreignKey = Str::snake(class_basename($this));
        }

        $query = $this->newQuery();

        $instance = new $related;

        return new EmbedsMany($query, $this, $instance, $localKey, $foreignKey, $relation);
    }

    /**
     * Define an embedded one-to-many relationship.
     * @param string $related
     * @param string|null $localKey
     * @param string|null $foreignKey
     * @param string|null $relation
     * @return EmbedsOne
     */
    protected function embedsOne(string $related, string $localKey = null, string $foreignKey = null, string $relation = null): EmbedsOne
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if ($relation === null) {
            [, $caller] = debug_backtrace(false);

            $relation = $caller['function'];
        }

        if ($localKey === null) {
            $localKey = $relation;
        }

        if ($foreignKey === null) {
            $foreignKey = Str::snake(class_basename($this));
        }

        $query = $this->newQuery();

        $instance = new $related;

        return new EmbedsOne($query, $this, $instance, $localKey, $foreignKey, $relation);
    }
}
