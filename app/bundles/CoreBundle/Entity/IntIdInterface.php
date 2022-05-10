<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface IntIdInterface
{
    /**
     * Can return null if not saved yet.
     *
     * @return ?int
     */
    public function getId();
}
