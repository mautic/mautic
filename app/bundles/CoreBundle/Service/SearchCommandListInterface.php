<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

interface SearchCommandListInterface
{
    /**
     * @return mixed[]
     */
    public function getList(): array;
}
