<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\PointBundle\Entity\PointRepository;
use Mautic\PointBundle\Model\PointModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly PointModel $pointModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::category(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
            SearchScopePresets::command(PointRepository::SEARCH_COMMAND_TYPE_GAIN),
            SearchScopePresets::command(PointRepository::SEARCH_COMMAND_TYPE_LOSS),
            SearchScopePresets::command(PointRepository::SEARCH_COMMAND_REPEATABLE),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->pointModel->getCommandList();
    }
}
