<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadSearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private readonly LeadModel $leadModel,
        private readonly FieldList $fieldList,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::field('firstname', 'mautic.lead.field.firstname'),
            SearchScopePresets::field('lastname', 'mautic.lead.field.lastname'),
            SearchScopePresets::email(),
            SearchScopePresets::field('country', 'mautic.lead.field.country'),
            SearchScopePresets::ids(),
            SearchScopePresets::name(),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.tag'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.company'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.list'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.stage'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.owner'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.campaign_membership'),
            SearchScopePresets::field('phone', 'mautic.lead.field.phone'),
            SearchScopePresets::field('city', 'mautic.lead.field.city'),
            SearchScopePresets::field('state', 'mautic.lead.field.state'),
            SearchScopePresets::field('zipcode', 'mautic.lead.field.zipcode'),
            SearchScopePresets::command('mautic.core.searchcommand.ip'),
            SearchScopePresets::isMine(),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.isunowned'),
            SearchScopePresets::command('mautic.lead.lead.searchcommand.dnc'),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->leadModel->getCommandList();
    }

    /**
     * @return array{label: string, translate: bool, indent?: bool}
     */
    protected function resolveDynamicLabel(string $commandKey, string $command): array
    {
        if (!str_starts_with($commandKey, 'mautic.')) {
            $fieldLabelKey = 'mautic.lead.field.'.$commandKey;
            if ($this->translator->trans($fieldLabelKey) !== $fieldLabelKey) {
                return ['label' => $fieldLabelKey, 'translate' => true];
            }

            $fieldLabels = $this->fieldList->getFieldList(false, true);
            if (isset($fieldLabels[$commandKey])) {
                return ['label' => $fieldLabels[$commandKey], 'translate' => false, 'indent' => true];
            }

            return ['label' => $command, 'translate' => false, 'indent' => true];
        }

        return parent::resolveDynamicLabel($commandKey, $command);
    }
}
