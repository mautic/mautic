<?php

namespace MauticPlugin\MauticIntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChange;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpressorHelperInterface;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var FieldChangeRepository
     */
    private $repo;

    /**
     * @var VariableExpressorHelperInterface
     */
    private $variableExpressor;

    /**
     * @param FieldChangeRepository            $repo
     * @param VariableExpressorHelperInterface $variableExpressor
     */
    public function __construct(FieldChangeRepository $repo, VariableExpressorHelperInterface $variableExpressor)
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
        ];
    }

    /**
     * @TODO Use VariableExpressorHelper to modify values
     * 
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
    }

    /**
     * @TODO Remove matching entries from FieldChangeRepository
     * 
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $this->repo->deleteEntitiesForObject($event->getLead()->getId(), Lead::class);
    }
}