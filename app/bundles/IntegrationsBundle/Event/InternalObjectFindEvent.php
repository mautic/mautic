<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\DateRange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InternalObjectFindEvent extends Event
{
    /**
     * @var int[]
     */
    private array $ids = [];

    private array $foundObjects = [];

    private ?DateRange $dateRange = null;

    private ?int $start = null;

    private ?int $limit = null;

    private array $fieldValues = [];

    public function __construct(
        private ObjectInterface $object,
    ) {
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @param int[]|string[] $ids
     */
    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    public function getFoundObjects(): array
    {
        return $this->foundObjects;
    }

    public function setFoundObjects(array $foundObjects): void
    {
        $this->foundObjects = $foundObjects;
    }

    public function getDateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    public function setDateRange(?DateRange $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function setStart(?int $start): void
    {
        $this->start = $start;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    public function getFieldValues(): array
    {
        return $this->fieldValues;
    }

    public function setFieldValues(array $fieldValues): void
    {
        $this->fieldValues = $fieldValues;
    }
}
