<?php

namespace MauticPlugin\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\IntegrationsBundle\Entity\FieldChange;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;

/**
 * Class LeadSubscriber
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepo;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpressor;

    /**
     * @var SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;

    /**
     * LeadSubscriber constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepo
     * @param VariableExpresserHelperInterface $variableExpressor
     * @param SyncIntegrationsHelper           $syncIntegrationsHelper
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepo,
        VariableExpresserHelperInterface $variableExpressor,
        SyncIntegrationsHelper $syncIntegrationsHelper
    ) {
        $this->fieldChangeRepo        = $fieldChangeRepo;
        $this->variableExpressor      = $variableExpressor;
        $this->syncIntegrationsHelper = $syncIntegrationsHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
            LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
            LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
        ];
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
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

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(MauticSyncDataExchange::OBJECT_CONTACT)) {
            // Only track if an integration is syncing with contacts
            return;
        }

        $changes       = $lead->getChanges(true);
        $toPersist     = [];
        $changedFields = [];

        if (!isset($changes['fields'])) {
            return;
        }

        foreach ($changes['fields'] as $key => list($oldValue, $newValue)) {
            $valueDAO          = $this->variableExpressor->encodeVariable($newValue);
            $changedFields[]   = $key;
            $fieldChangeEntity = (new FieldChange)
                ->setObjectType(Lead::class)
                ->setObjectId($lead->getId())
                ->setModifiedAt(new \DateTime)
                ->setColumnName($key)
                ->setColumnType($valueDAO->getType())
                ->setColumnValue($valueDAO->getValue());

            foreach ($this->syncIntegrationsHelper->getEnabledIntegrations() as $integrationName) {
                $integrationFieldChangeEntity = clone $fieldChangeEntity;
                $integrationFieldChangeEntity->setIntegration($integrationName);

                $toPersist[] = $integrationFieldChangeEntity;
            }
        }

        $this->fieldChangeRepo->deleteEntitiesForObjectByColumnName($lead->getId(), Lead::class, $changedFields);
        $this->fieldChangeRepo->saveEntities($toPersist);

        $this->fieldChangeRepo->clear();
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $this->fieldChangeRepo->deleteEntitiesForObject($event->getLead()->deletedId, Lead::class);
    }

    /**
     * @param Events\CompanyEvent $event
     */
    public function onCompanyPostSave(Events\CompanyEvent $event)
    {
        if (defined('MAUTIC_INTEGRATION_SYNC_IN_PROGRESS')) {
            // Don't track changes just made by an active sync
            return;
        }

        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(MauticSyncDataExchange::OBJECT_COMPANY)) {
            // Only track if an integration is syncing with companies
            return;
        }

        $company       = $event->getCompany();
        $changes       = $company->getChanges(true);
        $toPersist     = [];
        $changedFields = [];

        if (!isset($changes['fields'])) {
            return;
        }

        foreach ($changes['fields'] as $key => list($oldValue, $newValue)) {
            $valueDAO          = $this->variableExpressor->encodeVariable($newValue);
            $changedFields[]   = $key;
            $fieldChangeEntity = (new FieldChange)
                ->setObjectType(Lead::class)
                ->setObjectId($company->getId())
                ->setModifiedAt(new \DateTime)
                ->setColumnName($key)
                ->setColumnType($valueDAO->getType())
                ->setColumnValue($valueDAO->getValue());

            foreach ($this->syncIntegrationsHelper->getEnabledIntegrations() as $integrationName) {
                $integrationFieldChangeEntity = clone $fieldChangeEntity;
                $integrationFieldChangeEntity->setIntegration($integrationName);

                $toPersist[] = $integrationFieldChangeEntity;
            }
        }

        $this->fieldChangeRepo->deleteEntitiesForObjectByColumnName($company->getId(), Company::class, $changedFields);
        $this->fieldChangeRepo->saveEntities($toPersist);
        $this->fieldChangeRepo->clear();
    }

    /**
     * @param Events\CompanyEvent $event
     */
    public function onCompanyPostDelete(Events\CompanyEvent $event)
    {
        $this->fieldChangeRepo->deleteEntitiesForObject($event->getCompany()->deletedId, Company::class);
    }
}