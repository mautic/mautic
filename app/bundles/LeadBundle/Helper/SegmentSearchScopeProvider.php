<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SegmentSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private ListModel $listModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::name(),
            SearchScopePresets::category(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isMine(),
            SearchScopePresets::command('mautic.lead.list.searchcommand.isglobal'),
            SearchScopePresets::command('mautic.lead.list.searchcommand.filters_field'),
            SearchScopePresets::project(),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->listModel->getCommandList();
    }
}
