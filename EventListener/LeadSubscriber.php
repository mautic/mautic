<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    public static function getSubscribedEvents(): array
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
     *
     * @throws \MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
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


        if (!$this->syncIntegrationsHelper->hasObjectSyncEnabled(MauticSyncDataExchange::OBJECT_CONTACT)) {
            // Only track if an integration is syncing with contacts
            return;
        }

        $changes = $lead->getChanges(true);

        if (isset($changes['fields'])) {
            $this->recordFieldChanges($changes['fields'], $lead->getId(), Lead::class);
        }

        if (isset($changes['dnc_channel_status'])) {
            $dncChanges = [];
            foreach ($changes['dnc_channel_status'] as $channel => $change) {
                $oldValue = $change['old_reason'];
                $newValue = $change['reason'];

                $dncChanges['mautic_internal_dnc_'.$channel] = [$oldValue, $newValue];
            }

            $this->recordFieldChanges($dncChanges, $lead->getId(), Lead::class);
        }
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event): void
    {
        $this->fieldChangeRepo->deleteEntitiesForObject((int) $event->getLead()->deletedId, Lead::class);
    }

    /**
     * @param Events\CompanyEvent $event
     *
     * @throws \MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
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

        if (!isset($changes['fields'])) {
            return;
        }

        $this->recordFieldChanges($changes['fields'], $company->getId(), Company::class);
    }

    /**
     * @param Events\CompanyEvent $event
     */
    public function onCompanyPostDelete(Events\CompanyEvent $event): void
    {
        $this->fieldChangeRepo->deleteEntitiesForObject($event->getCompany()->deletedId, Company::class);
    }

    /**
     * @param array  $fieldChanges
     * @param int    $objectId
     * @param string $objectType
     *
     * @throws \MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException
     */
    private function recordFieldChanges(array $fieldChanges, int $objectId, string $objectType): void
    {
        $toPersist     = [];
        $changedFields = [];
        foreach ($fieldChanges as $key => list($oldValue, $newValue)) {
            $valueDAO          = $this->variableExpressor->encodeVariable($newValue);
            $changedFields[]   = $key;
            $fieldChangeEntity = (new FieldChange)
                ->setObjectType($objectType)
                ->setObjectId($objectId)
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

        $this->fieldChangeRepo->deleteEntitiesForObjectByColumnName($objectId, $objectType, $changedFields);
        $this->fieldChangeRepo->saveEntities($toPersist);

        $this->fieldChangeRepo->clear();
    }
}
