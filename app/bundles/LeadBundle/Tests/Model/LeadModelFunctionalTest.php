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
}
