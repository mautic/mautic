<?php

namespace MauticPlugin\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\IntegrationsBundle\Entity\FieldChange;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Helpers\VariableExpressor\VariableExpresserHelperInterface;

/**
 * Class LeadSubscriber
 *
 * @todo add support to clean up after a sync is complete in the case the integration errors for some reason as we do not wan't to lose changes for temporary sync issues
 */
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
        ];
    }

    /**
     * @todo only do this if a sync is enabled otherwise this will fill up fast
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

        $this->repo->clear();
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $this->repo->deleteEntitiesForObject($event->getLead()->deletedId, Lead::class);
    }
}