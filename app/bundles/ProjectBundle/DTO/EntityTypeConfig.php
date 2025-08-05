<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\DTO;

final readonly class EntityTypeConfig
{
    public function __construct(
        public string $entityClass,
        public string $label,
        public ?object $model = null,
    ) {
    }
}
