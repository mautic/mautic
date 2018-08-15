<?php

namespace MauticPlugin\MauticIntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChange;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\MauticIntegrationsBundle\Event\SyncEvent;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpresserHelperInterface;
use MauticPlugin\MauticIntegrationsBundle\IntegrationEvents;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var FieldChangeRepository
     */
    private $repo;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpressor;

    /**
     * @param FieldChangeRepository            $repo
     * @param VariableExpresserHelperInterface $variableExpressor
     */
    public function __construct(FieldChangeRepository $repo, VariableExpresserHelperInterface $variableExpressor)
    {
        $this->repo              = $repo;
        $this->variableExpressor = $variableExpressor;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE   => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE => ['onLeadPostDelete', 255],
            IntegrationEvents::ON_SYNC_COMPLETE => ['onSyncComplete', 0],
        ];
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $lead          = $event->getLead();
        $changes       = $lead->getChanges(true);
        $toPersist     = [];
        $changedFields = [];

        if (!isset($changes['fields'])) {
            return;
        }

        foreach ($changes['fields'] as $key => list($oldValue, $newValue)) {
            $valueDAO        = $this->variableExpressor->encodeVariable($newValue);
            $changedFields[] = $key;
            $toPersist[]     = (new FieldChange)
                ->setObjectType(Lead::class)
                ->setObjectId($lead->getId())
                ->setModifiedAt(new \DateTime)
                ->setColumnName($key)
                ->setColumnType($valueDAO->getType())
                ->setColumnValue($valueDAO->getValue());
        }

        $this->repo->deleteEntitiesForObjectByColumnName($lead->getId(), Lead::class, $changedFields);
        $this->repo->saveEntities($toPersist);

        $this->repo->clear();
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $this->repo->deleteEntitiesForObject($event->getLead()->deletedId, Lead::class);
    }

    /**
     * @param SyncEvent $event
     */
    public function onSyncComplete(SyncEvent $event)
    {
        $this->repo->deleteChangesBetween(MauticSyncDataExchange::CONTACT_OBJECT, $event->getStartDate(), $event->getEndDate());
    }
}