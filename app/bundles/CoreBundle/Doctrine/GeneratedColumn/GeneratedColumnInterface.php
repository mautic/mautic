<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

interface GeneratedColumnInterface
{
    public function getTableName(): string;

    public function getColumnName(): string;

    public function addIndexColumn(string $indexColumn): void;

    /**
     * If set then the line chart queries will use this column for the time unit instead of the original.
     */
    public function setOriginalDateColumn(string $originalDateColumn, string $timeUnit): void;

    public function getOriginalDateColumn(): ?string;

    public function getTimeUnit(): string;

    public function getAlterTableSql(): string;

    public function getColumnDefinition(): string;

    public function getIndexColumns(): array;

    public function getIndexName(): string;
}
