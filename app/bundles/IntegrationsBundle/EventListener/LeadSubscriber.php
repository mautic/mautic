<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\IntegrationsBundle\Entity\FieldChange;
use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\InternalCompanyEvent;
use Mautic\IntegrationsBundle\Event\InternalContactEvent;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldChangeRepository $fieldChangeRepo,
        private ObjectMappingRepository $objectMappingRepository,
        private VariableExpresserHelperInterface $variableExpressor,
        private SyncIntegrationsHelper $syncIntegrationsHelper,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
            LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
            LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
            LeadEvents::LEAD_COMPANY_CHANGE => ['onLeadCompanyChange', 128],
        ];
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function onLeadPostSave(Events\LeadEvent $event): void
    {
        $lead = $event->getLead();
        if ($lead->isAnonymous()) {
            // Do not track visitor changes
            return;
        }

        if (defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS')) {
            // Don't track changes just made by an active sync
            return;
        }

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(Contact::NAME)) {
            // Only track if an integration is syncing with contacts
            return;
        }

        $changes = $lead->getChanges(true);

        if (!empty($changes['owner'])) {
            // Force record of owner change if present in changelist
            $changes['fields']['owner_id'] = $changes['owner'];
        }

        if (!empty($changes['points'])) {
            // Add ability to update points custom field in target
            $changes['fields']['points'] = $changes['points'];
        }

        if (isset($changes['fields'])) {
            $this->recordFieldChanges($changes['fields'], $lead->getId(), Lead::class, $lead);
        }

        if (isset($changes['dnc_channel_status'])) {
            $dncChanges = [];
            foreach ($changes['dnc_channel_status'] as $channel => $change) {
                $oldValue = $change['old_reason'] ?? '';
                $newValue = $change['reason'];

                $dncChanges['mautic_internal_dnc_'.$channel] = [$oldValue, $newValue];
            }

            $this->recordFieldChanges($dncChanges, $lead->getId(), Lead::class, $lead);
        }
    }

    public function onLeadPostDelete(Events\LeadEvent $event): void
    {
        if ($event->getLead()->isAnonymous()) {
            return;
        }

        $this->fieldChangeRepo->deleteEntitiesForObject((int) $event->getLead()->deletedId, Lead::class);
        $this->objectMappingRepository->deleteEntitiesForObject((int) $event->getLead()->deletedId, MauticSyncDataExchange::OBJECT_CONTACT);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function onCompanyPostSave(Events\CompanyEvent $event): void
    {
        if (defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS')) {
            // Don't track changes just made by an active sync
            return;
        }

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(MauticSyncDataExchange::OBJECT_COMPANY)) {
            // Only track if an integration is syncing with companies
            return;
        }

        $company = $event->getCompany();
        $changes = $company->getChanges(true);

        if (!empty($changes['owner'])) {
            // Force record of owner change if present in changelist
            $changes['fields']['owner_id'] = $changes['owner'];
        }

        if (!isset($changes['fields'])) {
            return;
        }

        $this->recordFieldChanges($changes['fields'], $company->getId(), Company::class, $company);
    }

    public function onCompanyPostDelete(Events\CompanyEvent $event): void
    {
        $this->fieldChangeRepo->deleteEntitiesForObject((int) $event->getCompany()->deletedId, Company::class);
        $this->objectMappingRepository->deleteEntitiesForObject((int) $event->getCompany()->deletedId, MauticSyncDataExchange::OBJECT_COMPANY);
    }

    public function onLeadCompanyChange(Events\LeadChangeCompanyEvent $event): void
    {
        $lead = $event->getLead();

        // This mechanism is not able to record multiple company changes.
        $changes['company'] = [
            0 => '',
            1 => $lead->getCompany(),
        ];

        $this->recordFieldChanges($changes, $lead->getId(), Lead::class, $lead);
    }

    /**
     * @param int $objectId
     *
     * @throws IntegrationNotFoundException
     */
    private function recordFieldChanges(array $fieldChanges, $objectId, string $objectType, object $object): void
    {
        $toPersist     = [];
        $changedFields = [];
        $objectId      = (int) $objectId;

        foreach ($this->syncIntegrationsHelper->getEnabledIntegrations() as $integrationName) {
            try {
                $this->dispatchBeforeFieldChangesEvent($integrationName, $object);
            } catch (InvalidValueException) {
                continue; // Do not record changes for object and integration that has an invalid value.
            }

            foreach ($fieldChanges as $key => [$oldValue, $newValue]) {
                $valueDAO          = $this->variableExpressor->encodeVariable($newValue);
                $changedFields[]   = $key;
                $fieldChangeEntity = (new FieldChange())
                    ->setObjectType($objectType)
                    ->setObjectId($objectId)
                    ->setModifiedAt(new \DateTime())
                    ->setColumnName($key)
                    ->setColumnType($valueDAO->getType())
                    ->setColumnValue($valueDAO->getValue())
                    ->setIntegration($integrationName);

                $toPersist[] = $fieldChangeEntity;
            }
        }

        $this->fieldChangeRepo->deleteEntitiesForObjectByColumnName($objectId, $objectType, $changedFields);
        $this->fieldChangeRepo->saveEntities($toPersist);
        $this->fieldChangeRepo->detachEntities($toPersist);
    }

    /**
     * @throws InvalidValueException
     */
    private function dispatchBeforeFieldChangesEvent(string $integrationName, object $object): void
    {
        if ($object instanceof Lead) {
            if ($this->dispatcher->hasListeners(IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES)) {
                $this->dispatcher->dispatch(
                    new InternalContactEvent($integrationName, $object),
                    IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES
                );
            }

            return;
        }

        if ($object instanceof Company) {
            if ($this->dispatcher->hasListeners(IntegrationEvents::INTEGRATION_BEFORE_COMPANY_FIELD_CHANGES)) {
                $this->dispatcher->dispatch(
                    new InternalCompanyEvent($integrationName, $object),
                    IntegrationEvents::INTEGRATION_BEFORE_COMPANY_FIELD_CHANGES
                );
            }

            return;
        }

        throw new InvalidValueException('An object type should be specified. None matches.');
    }
}
