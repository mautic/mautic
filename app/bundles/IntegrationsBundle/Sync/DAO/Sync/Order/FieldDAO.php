<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

final readonly class FieldDAO
{
    public function __construct(
        private string $name,
        private NormalizedValueDAO $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
    }
}
