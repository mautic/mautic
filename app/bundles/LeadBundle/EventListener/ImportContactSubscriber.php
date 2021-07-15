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
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ImportContactSubscriber implements EventSubscriberInterface
{
    private FieldList $fieldList;
    private CorePermissions $corePermissions;
    private LeadModel $contactModel;

    public function __construct(
        FieldList $fieldList,
        CorePermissions $corePermissions,
        LeadModel $contactModel
    ) {
        $this->fieldList       = $fieldList;
        $this->corePermissions = $corePermissions;
        $this->contactModel    = $contactModel;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_INITIALIZE    => 'onImportInit',
            LeadEvents::IMPORT_ON_FIELD_MAPPING => 'onFieldMapping',
            LeadEvents::IMPORT_ON_PROCESS       => 'onImportProcess',
        ];
    }

    /**
     * @throws AccessDeniedException
     */
    public function onImportInit(ImportInitEvent $event): void
    {
        if ($event->importIsForRouteObject('contacts')) {
            if (!$this->corePermissions->isGranted('lead:imports:create')) {
                throw new AccessDeniedException('You do not have permission to import contacts');
            }

            $event->objectSingular = 'lead';
            $event->objectName     = 'mautic.lead.leads';
            $event->activeLink     = '#mautic_contact_index';
            $event->setIndexRoute('mautic_contact_index');
            $event->stopPropagation();
        }
    }

    public function onFieldMapping(ImportMappingEvent $event): void
    {
        if ($event->importIsForRouteObject('contacts')) {
            $specialFields = [
                'dateAdded'      => 'mautic.lead.import.label.dateAdded',
                'createdByUser'  => 'mautic.lead.import.label.createdByUser',
                'dateModified'   => 'mautic.lead.import.label.dateModified',
                'modifiedByUser' => 'mautic.lead.import.label.modifiedByUser',
                'lastActive'     => 'mautic.lead.import.label.lastActive',
                'dateIdentified' => 'mautic.lead.import.label.dateIdentified',
                'ip'             => 'mautic.lead.import.label.ip',
                'points'         => 'mautic.lead.import.label.points',
                'stage'          => 'mautic.lead.import.label.stage',
                'doNotEmail'     => 'mautic.lead.import.label.doNotEmail',
                'ownerusername'  => 'mautic.lead.import.label.ownerusername',
            ];

            // Add ID to lead fields to allow matching import contacts by identifier
            $contactFields = array_merge(['id' => 'mautic.lead.import.label.id'], $this->fieldList->getFieldList(false, false));

            $event->fields = [
                'mautic.lead.contact'        => $contactFields,
                'mautic.lead.company'        => $this->fieldList->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']),
                'mautic.lead.special_fields' => $specialFields,
            ];
        }
    }

    public function onImportProcess(ImportProcessEvent $event): void
    {
        if ($event->importIsForObject('lead')) {
            $merged = $this->contactModel->import(
                $event->import->getMatchedFields(),
                $event->rowData,
                $event->import->getDefault('owner'),
                $event->import->getDefault('list'),
                $event->import->getDefault('tags'),
                true,
                $event->eventLog,
                (int) $event->import->getId(),
                $event->import->getDefault('skip_if_exists')
            );
            $event->setWasMerged((bool) $merged);
            $event->stopPropagation();
        }
    }
}
