<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\FormBundle\Entity\Field;
use Mautic\ReportBundle\Event\ReportEventInterface;

class CompanyReportData
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function getCompanyData()
    {
        $companyColumns = $this->getCompanyColumns();
        $companyFields  = $this->fieldModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => 'f.object',
                        'expr'   => 'like',
                        'value'  => 'company',
                    ],
                ],
            ],
        ]);

        $companyColumns = array_merge($companyColumns, $this->getFieldColumns($companyFields, 'comp.'));

        return $companyColumns;
    }

    /**
     * @param ReportEventInterface $event
     *
     * @return bool
     */
    public function eventHasCompanyColumns(ReportEventInterface $event)
    {
        $companyColumns = $this->getCompanyColumns();
        foreach ($companyColumns as $key => $column) {
            if ($event->hasColumn($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    private function getCompanyColumns()
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
            'companies_lead.is_primary' => [
                'label' => 'mautic.lead.report.company.is_primary',
                'type'  => 'bool',
            ],
        ];
    }

    /**
     * @param Field[] $fields
     * @param string  $prefix
     *
     * @return array
     */
    private function getFieldColumns($fields, $prefix)
    {
        $columns = [];
        foreach ($fields as $f) {
            switch ($f->getType()) {
                case 'boolean':
                    $type = 'bool';
                    break;
                case 'date':
                    $type = 'date';
                    break;
                case 'datetime':
                    $type = 'datetime';
                    break;
                case 'time':
                    $type = 'time';
                    break;
                case 'url':
                    $type = 'url';
                    break;
                case 'email':
                    $type = 'email';
                    break;
                case 'number':
                    $type = 'float';
                    break;
                default:
                    $type = 'string';
                    break;
            }
            $columns[$prefix.$f->getAlias()] = [
                'label' => $f->getLabel(),
                'type'  => $type,
            ];
        }

        return $columns;
    }
}
