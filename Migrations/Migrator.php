<?php

namespace Libra\Dbal\Migrations;

use Illuminate\Console\View\Components\Warn;
use Illuminate\Database\Migrations\Migrator as BaseMigrator;

class Migrator extends BaseMigrator
{
    /**
     * @override 增加判断 migration 是否完整了
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int $batch
     * @param bool $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        $migration = $this->resolvePath($file);

        // @notice 判断 migration 文件是否完成了
        if (!property_exists($migration, 'version') || !$migration->version) {
            $this->write(Warn::class, $file . ' 没有完成');
            return;
        }

        parent::runUp($file, $batch, $pretend);
    }
}
