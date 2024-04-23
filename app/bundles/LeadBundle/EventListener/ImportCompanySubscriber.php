<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Event\ImportInitEvent;
use Mautic\LeadBundle\Event\ImportMappingEvent;
use Mautic\LeadBundle\Event\ImportProcessEvent;
use Mautic\LeadBundle\Event\ImportValidateEvent;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ImportCompanySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldList $fieldList,
        private CorePermissions $corePermissions,
        private CompanyModel $companyModel,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::IMPORT_ON_INITIALIZE    => ['onImportInit'],
            LeadEvents::IMPORT_ON_FIELD_MAPPING => ['onFieldMapping'],
            LeadEvents::IMPORT_ON_PROCESS       => ['onImportProcess'],
            LeadEvents::IMPORT_ON_VALIDATE      => ['onValidateImport'],
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

    public function onValidateImport(ImportValidateEvent $event): void
    {
        if (false === $event->importIsForRouteObject('companies')) {
            return;
        }

        $matchedFields = $event->getForm()->getData();
        $skipIfExists  = ArrayHelper::pickValue('skip_if_exists', $matchedFields, false);
        $event->setSkipIfExists((bool) $skipIfExists);
        unset($matchedFields['skip_if_exists']);
        $event->setOwnerId($this->handleValidateOwner($matchedFields));

        $matchedFields = array_map(
            fn ($value) => is_string($value) ? trim($value) : $value,
            array_filter($matchedFields)
        );

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
     * @param mixed[] $matchedFields
     */
    private function handleValidateOwner(array &$matchedFields): ?int
    {
        $owner = ArrayHelper::pickValue('owner', $matchedFields);

        return $owner ? $owner->getId() : null;
    }

    /**
     * Validate required fields.
     *
     * Required fields come through as ['alias' => 'label'], and
     * $matchedFields is a zero indexed array, so to calculate the
     * diff, we must array_flip($matchedFields) and compare on key.
     *
     * @param mixed[] $matchedFields
     */
    private function handleValidateRequired(ImportValidateEvent $event, array &$matchedFields): void
    {
        $requiredFields = $this->fieldList->getFieldList(false, false, [
            'isPublished' => true,
            'object'      => 'company',
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
                            '%fieldOrFields%'  => 1 === count($missingRequiredFields) ? 'field' : 'fields',
                        ],
                        'validators'
                    )
                )
            );
        }
    }
}
