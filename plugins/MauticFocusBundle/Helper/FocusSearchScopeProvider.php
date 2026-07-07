<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FocusSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private FocusModel $focusModel,
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
        return $this->focusModel->getCommandList();
    }
}
