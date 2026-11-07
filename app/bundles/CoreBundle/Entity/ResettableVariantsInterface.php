<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface ResettableVariantsInterface
{
    /**
     * @param string[]|string|int $relatedIds
     */
    public function resetVariants($relatedIds, string $date): void;
}
