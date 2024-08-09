<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

interface GeneratedColumnsInterface extends \Iterator, \Countable
{
    public function add(GeneratedColumn $generatedColumn): void;

    /**
     * @throws \UnexpectedValueException
     */
    public function getForOriginalDateColumnAndUnit(string $originalDateColumn, string $unit): GeneratedColumnInterface;
}
