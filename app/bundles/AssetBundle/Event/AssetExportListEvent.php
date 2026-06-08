<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

final class AssetExportListEvent extends CommonEvent
{
    /**
     * @var array<string>
     */
    private array $list = [];

    /**
     * @param list<array<string, array<string, mixed>>> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return list<array<string, array<string, mixed>>>
     */
    public function getEntityData(): array
    {
        return $this->data;
    }

    public function setList(string $item): void
    {
        if (!in_array($item, $this->list)) {
            $this->list[] = $item;
        }
    }

    /**
     * @return array<string>|null
     */
    public function getList(): ?array
    {
        return $this->list ?? null;
    }
}
