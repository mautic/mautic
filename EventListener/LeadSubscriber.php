<?php

namespace MauticPlugin\MauticIntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChange;
use MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpressorHelper;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE   => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE => ['onLeadPostDelete', 255],
        ];
    }

    /**
     * @TODO Use VariableExpressorHelper to modify values
     * 
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $repo          = $this->em->getRepository(FieldChange::class);
        $lead          = $event->getLead();
        $toPersist     = [];
        $changedFields = [];
        $expressor     = new VariableExpressorHelper;
        $changes       = $lead->getChanges(true);

        if (!isset($changes['fields'])) {
            return;
        }

        foreach ($changes['fields'] as $key => list($oldValue, $newValue)) {
            $changedFields[] = $key;
            $toPersist[]     = (new FieldChange)
                ->setObjectType(Lead::class)
                ->setObjectId($lead->getId())
                ->setModifiedAt(new \DateTime)
                ->setColumnName($key)
                ->setColumnType('?')
                ->setColumnValue($newValue);
        }
        
        $repo->deleteEntitiesForObjectByColumnName($lead->getId(), Lead::class, $changedFields);
        $repo->saveEntities($toPersist);
    }

    /**
     * @TODO Remove matching entries from FieldChangeRepository
     * 
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $repo = $this->em->getRepository(FieldChange::class);
        $lead = $event->getLead();

        $repo->deleteEntitiesForObject($lead->getId(), Lead::class);
    }
}