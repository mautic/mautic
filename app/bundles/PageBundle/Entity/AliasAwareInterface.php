<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Entity;

interface AliasAwareInterface
{
    public function getAlias(): ?string;
}
