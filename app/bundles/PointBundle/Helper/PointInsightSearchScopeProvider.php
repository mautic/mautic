<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\CoreBundle\Model\SearchCommandListInterface;
use Mautic\PointBundle\Model\InsightModel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointInsightSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        #[Autowire(service: InsightModel::class)]
        private readonly SearchCommandListInterface $insightModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->insightModel->getCommandList();
    }
}
