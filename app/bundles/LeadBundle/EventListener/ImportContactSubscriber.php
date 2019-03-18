<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
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
            LeadEvents::IMPORT_ON_VALIDATE      => 'onValidateImport',
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

    /**
     * @param ImportValidateEvent
     */
    public function onValidateImport(ImportValidateEvent $event)
    {
        if ($event->importIsForRouteObject('contacts') === false) {
            return;
        }

        $matchedFields = $event->getForm()->getData();

        $event->setOwnerId($this->handleValidateOwner($matchedFields));
        $event->setList($this->handleValidateList($matchedFields));
        $event->setTags($this->handleValidateTags($matchedFields));

        $matchedFields = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, array_filter($matchedFields));

        if (empty($matchedFields)) {
            $event->getForm()->addError(
                new FormError(
                    $this->translator->trans('mautic.lead.import.matchfields', [], 'validators')
                )
            );
        }

        $this->handleValidateRequired($event, $matchedFields);

        $event->setMatchedFields($matchedFields);
    }

    /**
     * @param array $matchedFields
     */
    private function handleValidateOwner(array &$matchedFields)
    {
        if (array_key_exists('owner', $matchedFields)) {
            $owner = $matchedFields['owner'];
            unset($matchedFields['owner']);

            return $owner ? $owner->getId() : null;
        }

        return null;
    }

    /**
     * @param array $matchedFields
     *
     * @return string
     */
    private function handleValidateList(array &$matchedFields)
    {
        if (array_key_exists('list', $matchedFields)) {
            $list = $matchedFields['list'];
            unset($matchedFields['list']);

            return $list;
        }

        return null;
    }

    /**
     * @param array $matchedFields
     *
     * @return array
     */
    private function handleValidateTags(array &$matchedFields)
    {
        if (array_key_exists('tags', $matchedFields)) {
            $tagCollection = $matchedFields['tags'];
            $tags          = [];
            foreach ($tagCollection as $tag) {
                $tags[] = $tag->getTag();
            }
            unset($matchedFields['tags']);

            return $tags;
        }

        return [];
    }

    /**
     * Validate required fields.
     *
     * Required fields come through as ['alias' => 'label'], and
     * $matchedFields is a zero indexed array, so to calculate the
     * diff, we must array_flip($matchedFields) and compare on key.
     *
     * @param ImportValidateEvent $event
     * @param array               $matchedFields
     */
    private function handleValidateRequired(ImportValidateEvent $event, array &$matchedFields)
    {
        $requiredFields = $this->fieldList->getFieldList(false, false, [
            'isPublished' => true,
            'object'      => 'lead',
            'isRequired'  => true,
        ]);

        $missingRequiredFields = array_diff_key($requiredFields, array_flip($matchedFields));

        if (count($missingRequiredFields)) {
            $event->getForm()->addError(
                new FormError(
                    $this->translator->trans(
                        'mautic.import.missing.required.fields',
                        [
                            '%requiredFields%' => implode(', ', $missingRequiredFields),
                            '%fieldOrFields%'  => count($missingRequiredFields) === 1 ? 'field' : 'fields',
                        ],
                        'validators'
                    )
                )
            );
        }
    }
}
