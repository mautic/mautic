<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImportCompanySubscriber extends CommonSubscriber
{
    /**
     * @var FieldList
     */
    private $fieldList;

    /**
     * @var CorePermissions
     */
    private $corePermissions;

    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * @param FieldList       $fieldList
     * @param CorePermissions $corePermissions
     * @param CompanyModel    $companyModel
     */
    public function __construct(
        FieldList $fieldList,
        CorePermissions $corePermissions,
        CompanyModel $companyModel
    ) {
        $this->fieldList       = $fieldList;
        $this->corePermissions = $corePermissions;
        $this->companyModel    = $companyModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::IMPORT_ON_INITIALIZE    => ['onImportInit'],
            LeadEvents::IMPORT_ON_FIELD_MAPPING => ['onFieldMapping'],
            LeadEvents::IMPORT_ON_PROCESS       => ['onImportProcess'],
        ];
    }

    /**
     * @param ImportInitEvent $event
     *
     * @throws AccessDeniedException
     */
    public function onImportInit(ImportInitEvent $event)
    {
        if ($event->importIsForRouteObject('companies')) {
            if (!$this->corePermissions->isGranted('lead:imports:create')) {
                throw new AccessDeniedException('You do not have permission to import companies');
            }

            $event->setObjectSingular('company');
            $event->setObjectName('mautic.lead.lead.companies');
            $event->setActiveLink('#mautic_company_index');
            $event->setIndexRoute('mautic_company_index');
            $event->stopPropagation();
        }
    }

    /**
     * @param ImportMappingEvent $event
     */
    public function onFieldMapping(ImportMappingEvent $event)
    {
        if ($event->importIsForRouteObject('companies')) {
            $specialFields = [
                'dateAdded'      => 'mautic.lead.import.label.dateAdded',
                'createdByUser'  => 'mautic.lead.import.label.createdByUser',
                'dateModified'   => 'mautic.lead.import.label.dateModified',
                'modifiedByUser' => 'mautic.lead.import.label.modifiedByUser',
            ];

            $event->setFields([
                'mautic.lead.company'        => $this->fieldList->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']),
                'mautic.lead.special_fields' => $specialFields,
            ]);
        }
    }

    /**
     * @param ImportProcessEvent $event
     */
    public function onImportProcess(ImportProcessEvent $event)
    {
        if ($event->importIsForObject('company')) {
            $event->setWasMerged($this->companyModel->import(
                $event->getImport()->getMatchedFields(),
                $event->getRowData(),
                $event->getImport()->getDefault('owner')
            ));
        }
    }
}
