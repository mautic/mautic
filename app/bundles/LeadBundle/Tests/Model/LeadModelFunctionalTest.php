<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\Lead;

class LeadModelFunctionalTest extends MauticWebTestCase
{
    public function testMergedContactFound()
    {
        $model = $this->container->get('mautic.lead.model.lead');

        $bob = new Lead();
        $bob->setFirstname('Bob')
            ->setLastname('Smith')
            ->setEmail('bob.smith@test.com');
        $model->saveEntity($bob);
        $bobId = $bob->getId();

        $jane = new Lead();
        $jane->setFirstname('Jane')
            ->setLastname('Smith')
            ->setEmail('jane.smith@test.com');
        $model->saveEntity($jane);
        $janeId = $jane->getId();

        $model->mergeLeads($bob, $jane, false);

        // Bob should have been merged into Jane
        $jane = $model->getEntity($janeId);
        $this->assertEquals($janeId, $jane->getId());

        // If Bob is queried, Jane should be returned
        $jane = $model->getEntity($bobId);
        $this->assertEquals($janeId, $jane->getId());

        // Merge Jane into a third contact
        $joey = new Lead();
        $joey->setFirstname('Joey')
            ->setLastname('Smith')
            ->setEmail('joey.smith@test.com');
        $model->saveEntity($joey);
        $joeyId = $joey->getId();

        $model->mergeLeads($jane, $joey, false);

        // Query for Bob which should now return Joey
        $joey = $model->getEntity($bobId);
        $this->assertEquals($joeyId, $joey->getId());

        // If Joey is deleted, querying for Bob or Jane should result in null
        $model->deleteEntity($joey);
        $bob = $model->getEntity($bobId);
        $this->assertNull($bob);
        $jane = $model->getEntity($janeId);
        $this->assertNull($jane);
    }

    public function testMergedContactsPointsAreAccurate()
    {
        $model = $this->container->get('mautic.lead.model.lead');
        /** @var EntityManager $em */
        $em   = $this->container->get('doctrine.orm.entity_manager');

        // Startout Jane with 50 points
        $jane = new Lead();
        $jane->setFirstname('Jane')
            ->setLastname('Smith')
            ->setEmail('jane.smith@test.com')
            ->setPoints(50);
        $model->saveEntity($jane);
        $em->clear(Lead::class);
        $jane = $model->getEntity($jane->getId());
        $this->assertEquals(50, $jane->getPoints());
        $janeId = $jane->getId();

        // Jane is currently a visitor on a different device with 3 points
        $visitor = new Lead();
        $visitor->setPoints(3);
        $model->saveEntity($visitor);
        $em->clear(Lead::class);
        $visitor = $model->getEntity($visitor->getId());
        $this->assertEquals(3, $visitor->getPoints());

        // Jane submits a form or something that identifies her so the visitor should be merged into Jane giving her 53 points
        $jane = $model->getEntity($janeId);
        // Jane should start out with 50 points
        $this->assertEquals(50, $jane->getPoints());
        // Jane should come out of the merge as Jane
        $jane = $model->mergeLeads($visitor, $jane, false);
        $this->assertEquals($janeId, $jane->getId());
        // Jane should now have 53 points
        $this->assertEquals(53, $jane->getPoints());
        $em->clear(Lead::class);
        // Jane should still have 53 points
        $jane = $model->getEntity($janeId);
        $this->assertEquals(53, $jane->getPoints());

        // Jane is on another device again and gets 3 points
        $visitor2 = new Lead();
        $visitor2->setPoints(3);
        $model->saveEntity($visitor2);
        $em->clear(Lead::class);
        $visitor2 = $model->getEntity($visitor2->getId());
        $this->assertEquals(3, $visitor2->getPoints());

        // Jane again identifies herself, gets merged into the new visitor and so should now have a total of 56 points
        $jane = $model->getEntity($janeId);
        $jane = $model->mergeLeads($visitor2, $jane, false);
        $this->assertEquals($janeId, $jane->getId());
        $em->clear(Lead::class);
        $jane = $model->getEntity($jane->getId());

        $this->assertEquals(56, $jane->getPoints());
    }
}
