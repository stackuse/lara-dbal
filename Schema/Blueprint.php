<?php

namespace Libra\Dbal\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;

class Blueprint extends BaseBlueprint
{
    /**
     * @new
     * Add nullable creation and update timestamps to the table.
     *
     * @return void
     */
    public function operators(): void
    {
        $this->unsignedBigInteger('created_by')->default(0);
        $this->unsignedBigInteger('updated_by')->default(0);
    }

    /**
     * @new
     * @param string $column
     * @param string $arrayType
     * @return ColumnDefinition
     */
    public function array(string $column, string $arrayType = 'int'): ColumnDefinition
    {
        return $this->addColumn('array', $column, compact('arrayType'));
    }

    /**
     * @override
     * 是否使用migrate的外键
     * @param string|array $columns
     * @param string|null $name
     *
     * @return ForeignKeyDefinition
     */
    public function foreign($columns, $name = null)
    {
        // 是否需要外键约束，生成model er图时有用
        if (config('database.foreign') === false) {
            $command = new ForeignKeyDefinition();
        } else {
            $command = parent::foreign($columns, $name);
        }
        return $command;
    }
}
