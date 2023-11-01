<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Symfony\Component\EventDispatcher\Event;

class GeneratedColumnsEvent extends Event
{
    /**
     * @var GeneratedColumns
     */
    private $generatedColumns;

    public function __construct()
    {
        $this->generatedColumns = new GeneratedColumns();
    }

    public function getGeneratedColumns(): GeneratedColumns
    {
        return $this->generatedColumns;
    }

    public function addGeneratedColumn(GeneratedColumn $generatedColumn): void
    {
        $this->generatedColumns->add($generatedColumn);
    }
}
