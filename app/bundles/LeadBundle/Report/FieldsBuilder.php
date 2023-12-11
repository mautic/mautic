<?php

namespace Mautic\LeadBundle\Report;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\UserBundle\Model\UserModel;

class FieldsBuilder
{
    public function __construct(
        private FieldModel $fieldModel,
        private ListModel $listModel,
        private UserModel $userModel,
        private LeadModel $leadModel
    ) {
    }

    /**
     * @param string $prefix
     */
    public function getLeadFieldsColumns($prefix): array
    {
        $baseColumns  = $this->getBaseLeadColumns();
        $leadFields   = $this->fieldModel->getLeadFields();
        $fieldColumns = $this->getFieldColumns($leadFields, $prefix);

        return array_merge($baseColumns, $fieldColumns);
    }

    /**
     * @param string $prefix
     * @param string $segmentPrefix
     */
    public function getLeadFilter($prefix, $segmentPrefix): array
    {
        $filters = $this->getLeadFieldsColumns($prefix);

        $segmentPrefix = $this->sanitizePrefix($segmentPrefix);
        $prefix        = $this->sanitizePrefix($prefix);

        // Append segment filters
        $userSegments = $this->listModel->getUserLists();

        $list = [];
        foreach ($userSegments as $segment) {
            $list[$segment['id']] = $segment['name'];
        }

        $segmentKey           = $segmentPrefix.'leadlist_id';
        $filters[$segmentKey] = [
            'alias'     => 'segment_id',
            'label'     => 'mautic.core.filter.lists',
            'type'      => 'select',
            'list'      => $list,
            'operators' => [
                'eq' => 'mautic.core.operator.equals',
            ],
        ];

        $aTags     = [];
        $aTagsList = $this->leadModel->getTagList();
        foreach ($aTagsList as $aTemp) {
            $aTags[$aTemp['value']] = $aTemp['label'];
        }

        $filters['tag'] = [
            'label'     => 'mautic.core.filter.tags',
            'type'      => 'multiselect',
            'list'      => $aTags,
            'operators' => [
                'in'       => 'mautic.core.operator.in',
                'notIn'    => 'mautic.core.operator.notin',
                'empty'    => 'mautic.core.operator.isempty',
                'notEmpty' => 'mautic.core.operator.isnotempty',
            ],
        ];

        $ownerPrefix           = $prefix.'owner_id';
        $ownersList            = [];
        $owners                = $this->userModel->getUserList('', 0);
        foreach ($owners as $owner) {
            $ownersList[$owner['id']] = sprintf('%s %s', $owner['firstName'], $owner['lastName']);
        }
        $filters[$ownerPrefix] = [
            'label' => 'mautic.lead.list.filter.owner',
            'type'  => 'select',
            'list'  => $ownersList,
        ];

        return $filters;
    }

    /**
     * @param string $prefix
     */
    public function getCompanyFieldsColumns($prefix): array
    {
        $baseColumns   = $this->getBaseCompanyColumns();
        $companyFields = $this->fieldModel->getCompanyFields();
        $fieldColumns  = $this->getFieldColumns($companyFields, $prefix);

        return array_merge($baseColumns, $fieldColumns);
    }

    private function getBaseLeadColumns(): array
    {
        return [
            'l.id' => [
                'label' => 'mautic.lead.report.contact_id',
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
            ],
            'i.ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'text',
            ],
            'l.date_identified' => [
                'label'          => 'mautic.lead.report.date_identified',
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(l.date_identified)',
            ],
            'l.points' => [
                'label' => 'mautic.lead.points',
                'type'  => 'int',
            ],
            'l.owner_id' => [
                'label' => 'mautic.lead.report.owner_id',
                'type'  => 'int',
            ],
            'u.first_name' => [
                'label' => 'mautic.lead.report.owner_firstname',
                'type'  => 'string',
            ],
            'u.last_name' => [
                'label' => 'mautic.lead.report.owner_lastname',
                'type'  => 'string',
            ],
        ];
    }

    private function getBaseCompanyColumns(): array
    {
        return [
            'comp.id' => [
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyname' => [
                'label' => 'mautic.lead.report.company.company_name',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycity' => [
                'label' => 'mautic.lead.report.company.company_city',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companystate' => [
                'label' => 'mautic.lead.report.company.company_state',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companycountry' => [
                'label' => 'mautic.lead.report.company.company_country',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
            'comp.companyindustry' => [
                'label' => 'mautic.lead.report.company.company_industry',
                'type'  => 'string',
                'link'  => 'mautic_company_action',
            ],
        ];
    }

    /**
     * @param LeadField[] $fields
     * @param string      $prefix
     */
    private function getFieldColumns($fields, $prefix): array
    {
        $prefix = $this->sanitizePrefix($prefix);

        $columns = [];
        foreach ($fields as $field) {
            $type = match ($field->getType()) {
                'boolean'  => 'bool',
                'date'     => 'date',
                'datetime' => 'datetime',
                'time'     => 'time',
                'url'      => 'url',
                'email'    => 'email',
                'number'   => 'float',
                default    => 'string',
            };
            $columns[$prefix.$field->getAlias()] = [
                'label' => $field->getLabel(),
                'type'  => $type,
            ];
        }

        return $columns;
    }

    /**
     * @param string $prefix
     */
    private function sanitizePrefix($prefix): string
    {
        if (!str_contains($prefix, '.')) {
            $prefix .= '.';
        }

        return $prefix;
    }
}
