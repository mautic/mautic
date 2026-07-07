<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\PointBundle\Model\PointGroupModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PointGroupSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private PointGroupModel $pointGroupModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::ids(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isMine(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->pointGroupModel->getCommandList();
    }
}
