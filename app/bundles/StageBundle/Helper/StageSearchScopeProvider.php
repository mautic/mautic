<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class StageSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private StageModel $stageModel,
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
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->stageModel->getCommandList();
    }
}
