<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Service\SearchCommandListInterface;

class SearchCommandListExtension
{
    public function __construct(
        protected SearchCommandListInterface $searchCommandList,
    ) {
    }

    /**
     * @return mixed[]
     */
    #[\Twig\Attribute\AsTwigFunction('searchCommandList', isSafe: ['all'])]
    public function getSearchCommandList(): array
    {
        return $this->searchCommandList->getList();
    }
}
