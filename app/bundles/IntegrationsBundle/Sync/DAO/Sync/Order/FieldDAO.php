<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class FieldDAO
{
    /**
     * @param string $name
     */
    public function __construct(
        private $name,
        private NormalizedValueDAO $value,
    ) {
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getValue(): NormalizedValueDAO
    {
        return $this->value;
    }
}
