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

        $lead1 = new Lead();
        $lead1->setFirstname('Bob')
            ->setLastname('Smith')
            ->setEmail('bob.smith@test.com');
        $model->saveEntity($lead1);
        $lead1Id = $lead1->getId();

        $lead2 = new Lead();
        $lead2->setFirstname('Jane')
            ->setLastname('Smith')
            ->setEmail('jane.smith@test.com');
        $model->saveEntity($lead2);
        $lead2Id = $lead2->getId();

        $model->mergeLeads($lead1, $lead2, false);

        // Bob should have been merged into Jane
        $jane = $model->getEntity($lead2Id);
        $this->assertEquals($lead2Id, $jane->getId());

        // If Bob is queried, Jane should be returned
        $jane = $model->getEntity($lead1Id);
        $this->assertEquals($lead2Id, $jane->getId());

        // If Jane is deleted, querying for Bob should result in null
        $model->deleteEntity($jane);
        $bob = $model->getEntity($lead1Id);
        $this->assertNull($bob);
    }
}
