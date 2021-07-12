<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ImportCompanySubscriber implements EventSubscriberInterface
{
    private FieldList $fieldList;
    private CorePermissions $corePermissions;
    private CompanyModel $companyModel;

    public function __construct(
        FieldList $fieldList,
        CorePermissions $corePermissions,
        CompanyModel $companyModel
    ) {
        $this->fieldList       = $fieldList;
        $this->corePermissions = $corePermissions;
        $this->companyModel    = $companyModel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_INITIALIZE    => ['onImportInit'],
            LeadEvents::IMPORT_ON_FIELD_MAPPING => ['onFieldMapping'],
            LeadEvents::IMPORT_ON_PROCESS       => ['onImportProcess'],
        ];
    }

    /**
     * @throws AccessDeniedException
     */
    public function onImportInit(ImportInitEvent $event): void
    {
        if ($event->importIsForRouteObject('companies')) {
            if (!$this->corePermissions->isGranted('lead:imports:create')) {
                throw new AccessDeniedException('You do not have permission to import companies');
            }

            $event->objectSingular = 'company';
            $event->objectName     = 'mautic.lead.lead.companies';
            $event->activeLink     = '#mautic_company_index';
            $event->setIndexRoute('mautic_company_index');
            $event->stopPropagation();
        }
    }

    public function onFieldMapping(ImportMappingEvent $event): void
    {
        if ($event->importIsForRouteObject('companies')) {
            $specialFields = [
                'dateAdded'      => 'mautic.lead.import.label.dateAdded',
                'createdByUser'  => 'mautic.lead.import.label.createdByUser',
                'dateModified'   => 'mautic.lead.import.label.dateModified',
                'modifiedByUser' => 'mautic.lead.import.label.modifiedByUser',
            ];

            $event->fields = [
                'mautic.lead.company'        => $this->fieldList->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']),
                'mautic.lead.special_fields' => $specialFields,
            ];
        }
    }

    public function onImportProcess(ImportProcessEvent $event): void
    {
        if ($event->importIsForObject('company')) {
            $merged = $this->companyModel->import(
                $event->import->getMatchedFields(),
                $event->rowData,
                $event->import->getDefault('owner'),
                $event->import->getDefault('skip_if_exists')
            );
            $event->setWasMerged((bool) $merged);
            $event->stopPropagation();
        }
    }
}
