<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TriggerSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private TriggerModel $triggerModel,
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
        return $this->triggerModel->getCommandList();
    }
}
