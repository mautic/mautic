<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractSearchScopeProvider;
use Mautic\CoreBundle\Helper\SearchScopePresets;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanySearchScopeProvider extends AbstractSearchScopeProvider
{
    public function __construct(
        private CompanyModel $companyModel,
        private FieldList $fieldList,
        TranslatorInterface $translator,
    ) {
        parent::__construct($translator);
    }

    protected function getPinnedScopes(): array
    {
        return [
            SearchScopePresets::standard(),
            SearchScopePresets::field('companyname', 'mautic.lead.field.companyname'),
            SearchScopePresets::field('companyemail', 'mautic.lead.field.companyemail'),
            SearchScopePresets::ids(),
            SearchScopePresets::category(),
            SearchScopePresets::isPublished(),
            SearchScopePresets::isUnpublished(),
            SearchScopePresets::isUncategorized(),
            SearchScopePresets::isMine(),
            SearchScopePresets::project(),
            SearchScopePresets::field('companycity', 'mautic.lead.field.companycity'),
            SearchScopePresets::field('companystate', 'mautic.lead.field.companystate'),
            SearchScopePresets::field('companycountry', 'mautic.lead.field.companycountry'),
            SearchScopePresets::field('companyzipcode', 'mautic.lead.field.companyzipcode'),
            SearchScopePresets::field('companyphone', 'mautic.lead.field.companyphone'),
            SearchScopePresets::field('companywebsite', 'mautic.lead.field.companywebsite'),
        ];
    }

    protected function getAdditionalCommandKeys(): array
    {
        return $this->companyModel->getCommandList();
    }

    protected function resolveDynamicLabel(string $commandKey, string $command): array
    {
        if (!str_starts_with($commandKey, 'mautic.')) {
            $fieldLabelKey = 'mautic.lead.field.'.$commandKey;
            if ($this->translator->trans($fieldLabelKey) !== $fieldLabelKey) {
                return ['label' => $fieldLabelKey, 'translate' => true];
            }

            $fieldLabels = $this->fieldList->getFieldList(false, true, ['isPublished' => true, 'object' => 'company']);
            if (isset($fieldLabels[$commandKey])) {
                return ['label' => $fieldLabels[$commandKey], 'translate' => false];
            }

            return ['label' => $command, 'translate' => false];
        }

        return parent::resolveDynamicLabel($commandKey, $command);
    }
}
