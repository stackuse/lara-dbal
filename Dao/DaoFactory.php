<?php

namespace Libra\Dbal\Dao;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

abstract class DaoFactory extends Factory
{
    /**
     * @override 符合新的目录规范
     */
    public function modelName(): string
    {
        $resolver = static::$modelNameResolver ?? function (self $factory) {
            $namespaceFactoryBasename = Str::replaceLast(
                'Factory', '', Str::replaceFirst(static::$namespace, '', get_class($factory))
            );

            $factoryBasename = Str::replaceLast('Factory', '', class_basename($factory));

            $appNamespace = static::appNamespace();

            $basename = explode('\\', $namespaceFactoryBasename);

            $className = count($basename) === 1 ? $appNamespace . 'Models\\' . $factoryBasename : $appNamespace . $basename[1] . '\\Models\\' . $factoryBasename;
            if (!class_exists($className)) {
                throw new ModelNotFoundException($className);
            }
            return $className;
        };

        return $this->model ?? $resolver($this);
    }
}
