<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class LeadRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private \Mautic\LeadBundle\Entity\Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = $this->createLead();
    }

    public function testPointsAreAdded(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100);

        $model->saveEntity($this->lead);

        $this->assertEquals(200, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreSubtracted(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100, Lead::POINTS_SUBTRACT);

        $model->saveEntity($this->lead);

        $this->assertEquals(0, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(0, $changes['points'][1]);
    }

    public function testPointsAreMultiplied(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(2, Lead::POINTS_MULTIPLY);

        $model->saveEntity($this->lead);

        $this->assertEquals(200, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreDivided(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(2, Lead::POINTS_DIVIDE);

        $model->saveEntity($this->lead);

        $this->assertEquals(50, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(50, $changes['points'][1]);
    }

    public function testMixedOperatorPointsAreCalculated(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100, Lead::POINTS_SUBTRACT);
        $this->lead->adjustPoints(120, Lead::POINTS_ADD);
        $this->lead->adjustPoints(2, Lead::POINTS_MULTIPLY);
        $this->lead->adjustPoints(4, Lead::POINTS_DIVIDE);

        $model->saveEntity($this->lead);

        $this->assertEquals(60, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(60, $changes['points'][1]);
    }

    public function testMixedModelAndRepositorySavesDoNotDoublePoints(): void
    {
        $model = self::getContainer()->get('mautic.lead.model.lead');
        $this->lead->adjustPoints(120, Lead::POINTS_ADD);
        $model->saveEntity($this->lead);
        // Changes should be stored with points
        $changes = $this->lead->getChanges(true);
        $this->assertEquals(220, $changes['points'][1]);
        // Points should now not be in changes
        $model->saveEntity($this->lead);
        $changes = $this->lead->getChanges(true);
        $this->assertFalse(isset($changes['points']));
        // Points should remain the same
        $model->saveEntity($this->lead);
        $this->em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->saveEntity($this->lead);
        $this->assertEquals(220, $this->lead->getPoints());
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setPoints(100);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }
}
