<?php

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LeadModelFunctionalTest extends MauticMysqlTestCase
{
    private $pointsAdded = false;

    public function testSavingPrimaryCompanyAfterPointsAreSetByListenerAreNotResetToDefaultOf0BecauseOfPointsFieldDefaultIs0(): void
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = self::$container->get('event_dispatcher');
        $eventDispatcher->addListener(LeadEvents::LEAD_POST_SAVE, [$this, 'addPointsListener']);

        /** @var LeadModel $model */
        $model = self::$container->get('mautic.lead.model.lead');
        /** @var EntityManager $em */
        $em   = self::$container->get('doctrine.orm.entity_manager');

        // Set company to trigger setPrimaryCompany()
        $lead = new Lead();
        $data = ['email' => 'pointtest@test.com', 'company' => 'PointTest'];
        $model->setFieldValues($lead, $data, false, true, true);

        // Save to trigger points listener and setting primary company
        $model->saveEntity($lead);

        // Clear from doctrine memory so we get a fresh entity to ensure the points are definitely saved
        $em->clear(Lead::class);
        $lead = $model->getEntity($lead->getId());

        $this->assertEquals(10, $lead->getPoints());
    }

    /**
     * Simulate a PointModel::triggerAction.
     */
    public function addPointsListener(LeadEvent $event): void
    {
        // Prevent a loop
        if ($this->pointsAdded) {
            return;
        }

        $this->pointsAdded = true;

        $lead = $event->getLead();
        $lead->adjustPoints(10);

        /** @var LeadModel $model */
        $model = self::$container->get('mautic.lead.model.lead');
        $model->saveEntity($lead);
    }
}
