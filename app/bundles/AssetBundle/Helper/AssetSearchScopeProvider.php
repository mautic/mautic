<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Helper;

use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssetSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private AssetModel $assetModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::category(),
            SearchScopePresets::command('mautic.asset.asset.searchcommand.lang'),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
            SearchScopePresets::command('mautic.asset.asset.searchcommand.isexpired'),
            SearchScopePresets::command('mautic.asset.asset.searchcommand.ispending'),
            SearchScopePresets::project(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->assetModel->getCommandList();
    }
}
