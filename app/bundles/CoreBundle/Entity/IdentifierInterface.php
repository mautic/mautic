<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface IdentifierInterface
{
    /**
     * @return int|null
     */
    public function getId();
}
