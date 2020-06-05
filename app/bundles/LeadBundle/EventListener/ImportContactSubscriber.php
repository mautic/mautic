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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImportContactSubscriber extends EventSubscriberInterface
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
     * @var LeadModel
     */
    private $contactModel;

    public function __construct(
        FieldList $fieldList,
        CorePermissions $corePermissions,
        LeadModel $contactModel
    ) {
        $this->fieldList       = $fieldList;
        $this->corePermissions = $corePermissions;
        $this->contactModel    = $contactModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
    public function onImportInit(ImportInitEvent $event)
    {
        if ($event->importIsForRouteObject('contacts')) {
            if (!$this->corePermissions->isGranted('lead:imports:create')) {
                throw new AccessDeniedException('You do not have permission to import contacts');
            }

            $event->setObjectSingular('lead');
            $event->setObjectName('mautic.lead.leads');
            $event->setActiveLink('#mautic_contact_index');
            $event->setIndexRoute('mautic_contact_index');
            $event->stopPropagation();
        }
    }

    public function onFieldMapping(ImportMappingEvent $event)
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

            $event->setFields([
                'mautic.lead.contact'        => $this->fieldList->getFieldList(false, false),
                'mautic.lead.company'        => $this->fieldList->getFieldList(false, false, ['isPublished' => true, 'object' => 'company']),
                'mautic.lead.special_fields' => $specialFields,
            ]);
        }
    }

    public function onImportProcess(ImportProcessEvent $event)
    {
        if ($event->importIsForObject('lead')) {
            $import = $event->getImport();
            $merged = $this->contactModel->import(
                $import->getMatchedFields(),
                $event->getRowData(),
                $import->getDefault('owner'),
                $import->getDefault('list'),
                $import->getDefault('tags'),
                true,
                $event->getEventLog(),
                (int) $import->getId()
            );
            $event->setWasMerged((bool) $merged);
            $event->stopPropagation();
        }
    }
}
