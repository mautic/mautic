<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface IdentifierInterface
{
    /**
     * @return int|string|null
     */
    public function getId();
}
