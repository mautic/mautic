<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Service\SearchCommandListInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class SearchCommandListExtension
{
    public function __construct(
        private SearchCommandListInterface $searchCommandList,
    ) {
    }

    /**
     * @return mixed[]
     */
    #[AsTwigFunction(name: 'searchCommandList', isSafe: ['all'])]
    public function getSearchCommandList(): array
    {
        return $this->searchCommandList->getList();
    }
}
