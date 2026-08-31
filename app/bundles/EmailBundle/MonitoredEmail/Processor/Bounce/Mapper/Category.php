<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper;

final readonly class Category
{
    public function __construct(
        private string $category,
        private string $type,
        private bool $isPermanent,
    ) {
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isPermanent(): bool
    {
        return $this->isPermanent;
    }
}
