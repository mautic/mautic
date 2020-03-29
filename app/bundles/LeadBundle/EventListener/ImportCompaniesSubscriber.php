<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\ImportBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;

class ImportCompaniesSubscriber extends CommonSubscriber
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * ImportCompaniesSubscriber constructor.
     *
     * @param FieldModel   $fieldModel
     * @param CompanyModel $companyModel
     */
    public function __construct(FieldModel $fieldModel, CompanyModel $companyModel)
    {
        $this->fieldModel   = $fieldModel;
        $this->companyModel = $companyModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::IMPORT_BUILDER => ['importBuilder', 0],
        ];
    }

    /**
     * @param ImportBuilderEvent $event
     */
    public function importBuilder(ImportBuilderEvent $event)
    {
        if ($event->getObject() === 'companies') {
            $event->setObjectFromRequest('company');
            $fields = [
                'mautic.lead.company'=> $this->fieldModel->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']),
            ];

            $event->setRoute('mautic_company_index');
            $event->setActiveLink('#mautic_company_index');
            $event->setFields($fields);
            $event->setModel($this->companyModel);
        }
    }
}
