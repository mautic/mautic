<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\IntegrationToMauticSyncCompletedEvent;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\StageBundle\Model\StageModel;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Sync\ContactExtras;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Applies what the field mapping cannot to the contacts pulled from HubSpot: the lifecycle stage becomes the contact's
 * stage when it has none, and the contact joins its HubSpot company once that company is synced.
 */
final readonly class HubspotSyncSubscriber
{
    public function __construct(
        private ContactExtras $contactExtras,
        private ObjectMappingRepository $objectMappingRepository,
        private LeadModel $leadModel,
        private StageModel $stageModel,
        private StageRepository $stageRepository,
    ) {
    }

    #[AsEventListener]
    public function onIntegrationToMauticSyncCompleted(IntegrationToMauticSyncCompletedEvent $event): void
    {
        if (HubspotIntegration::NAME !== $event->getIntegration()) {
            return;
        }

        foreach ($event->getOrderResults()->getObjectMappings(HubspotClient::CONTACTS) as $mapping) {
            $extras = $this->contactExtras->get((string) $mapping->getIntegrationObjectId());
            if (null === $extras) {
                continue;
            }

            $contact = $this->leadModel->getEntity($mapping->getInternalObjectId());
            if (!$contact instanceof Lead) {
                continue;
            }

            if (null !== $extras['stage'] && null === $contact->getStage()) {
                $this->setStage($contact, $this->getStage($extras['stage']));
                $this->leadModel->saveEntity($contact, false);
            }

            if (null !== $extras['companyId']) {
                $company = $this->objectMappingRepository->getInternalObject(HubspotIntegration::NAME, HubspotClient::COMPANIES, $extras['companyId'], Company::NAME);
                if (null !== $company) {
                    $this->leadModel->addToCompany($contact, (int) $company['internal_object_id']);
                }
            }
        }
    }

    private function setStage(Lead $contact, Stage $stage): void
    {
        $contact->setStage($stage);

        $log = new StagesChangeLog();
        $log->setStage($stage);
        $log->setEventName($stage->getId().':'.$stage->getName());
        $log->setLead($contact);
        $log->setActionName(HubspotIntegration::NAME);
        $log->setDateAdded(new \DateTime());
        $contact->stageChangeLog($log);
    }

    private function getStage(string $name): Stage
    {
        $stage = $this->stageRepository->getStageByName($name);
        if ($stage instanceof Stage) {
            return $stage;
        }

        $stage = new Stage();
        $stage->setName($name);
        $this->stageModel->saveEntity($stage);

        return $stage;
    }
}
