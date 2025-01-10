<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Service\SearchCommandListInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SearchCommandListExtension extends AbstractExtension
{
    public function __construct(
        protected SearchCommandListInterface $searchCommandList,
    ) {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('searchCommandList', [$this, 'getSearchCommandList'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @return mixed[]
     */
    public function getSearchCommandList(): array
    {
        return $this->searchCommandList->getList();
    }
}
