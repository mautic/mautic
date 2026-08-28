<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FieldSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly FieldModel $fieldModel,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isMine(),
            SearchScopePresets::command('mautic.lead.field.searchcommand.isindexed'),
            SearchScopePresets::command('mautic.lead.field.searchcommand.isunique'),
            SearchScopePresets::command('mautic.lead.field.searchcommand.type'),
            SearchScopePresets::command('mautic.lead.field.searchcommand.group'),
            SearchScopePresets::ids(),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->fieldModel->getCommandList();
    }
}
