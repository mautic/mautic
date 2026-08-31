<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Service\SearchCommandListInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SearchCommandListExtension extends AbstractExtension
{
    public function __construct(
        private readonly SearchCommandListInterface $searchCommandList,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('searchCommandList', $this->getSearchCommandList(...), ['is_safe' => ['all']]),
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
