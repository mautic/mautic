<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DynamicContentSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private DynamicContentModel $dynamicContentModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::lang(),
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
        return $this->dynamicContentModel->getCommandList();
    }
}
