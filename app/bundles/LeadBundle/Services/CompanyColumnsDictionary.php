<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Field\FieldList;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanyColumnsDictionary
{
    /**
     * @var array<string, string>
     */
    private array $cachedChoices = [];

    public function __construct(
        private readonly FieldList $fieldList,
        private readonly TranslatorInterface $translator,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * @return array<string, string> ordered column alias => label
     */
    public function getColumns(): array
    {
        $rawColumns = $this->coreParametersHelper->get('company_columns', []);
        if (!\is_array($rawColumns)) {
            $rawColumns = [];
        }

        $columns = array_flip($rawColumns);
        $fields  = $this->getFields();

        foreach ($columns as $alias => &$column) {
            if (isset($fields[(string) $alias])) {
                $column = $fields[(string) $alias];
            }
        }

        return $columns;
    }

    /**
     * @return array<string, string> alias => label available for choices
     */
    public function getFields(): array
    {
        if ([] === $this->cachedChoices) {
            $this->cachedChoices = [
                'companyname'    => $this->translator->trans('mautic.company.name'),
                'companyemail'   => $this->translator->trans('mautic.company.email'),
                'companywebsite' => $this->translator->trans('mautic.company.website'),
                'score'          => $this->translator->trans('mautic.company.score'),
                'leadcount'      => $this->translator->trans('mautic.lead.list.thead.leadcount'),
                'id'             => $this->translator->trans('mautic.core.id'),
            ];

            $this->cachedChoices += $this->fieldList->getFieldList(
                false,
                true,
                ['isPublished' => true, 'object' => 'company']
            );
        }

        return $this->cachedChoices;
    }
}
