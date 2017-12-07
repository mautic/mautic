<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\Lead;

class LeadRepositoryFunctionalTest extends MauticWebTestCase
{
    public function testPointsAreAdded()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $lead = $model->getEntity(1);
        $lead->adjustPoints(100);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreSubtracted()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $lead = $model->getEntity(1);
        $lead->adjustPoints(100, Lead::POINTS_SUBTRACT);

        $model->saveEntity($lead);

        $this->assertEquals(0, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(0, $changes['points'][1]);
    }

    public function testPointsAreMultiplied()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $lead = $model->getEntity(1);
        $lead->adjustPoints(2, Lead::POINTS_MULTIPLY);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreDivided()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $lead = $model->getEntity(1);
        $lead->adjustPoints(2, Lead::POINTS_DIVIDE);

        $model->saveEntity($lead);

        $this->assertEquals(50, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(50, $changes['points'][1]);
    }

    public function testMixedOperatorPointsAreCalculated()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $lead = $model->getEntity(1);
        $lead->adjustPoints(100, Lead::POINTS_SUBTRACT);
        $lead->adjustPoints(120, Lead::POINTS_ADD);
        $lead->adjustPoints(2, Lead::POINTS_MULTIPLY);
        $lead->adjustPoints(4, Lead::POINTS_DIVIDE);

        $model->saveEntity($lead);

        $this->assertEquals(60, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(60, $changes['points'][1]);
    }
}
