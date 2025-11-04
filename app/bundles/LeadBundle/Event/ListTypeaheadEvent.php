<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ListTypeaheadEvent extends Event
{
    /**
     * @var mixed[]
     */
    private array $dataArray = [];

    public function __construct(private string $fieldAlias, private string $filter)
    {
    }

    public function getFieldAlias(): string
    {
        return $this->fieldAlias;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    /**
     * @return mixed[]
     */
    public function getDataArray(): array
    {
        return $this->dataArray;
    }

    /**
     * @param mixed[] $dataArray
     */
    public function setDataArray(array $dataArray): void
    {
        $this->dataArray = $dataArray;
    }
}
