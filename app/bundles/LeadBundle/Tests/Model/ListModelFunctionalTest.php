<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;

class ListModelFunctionalTest extends MauticWebTestCase
{
    public function testPublicSegmentsInContactPreferences()
    {
        /** @var LeadListRepository $repo */
        $repo = $this->em->getRepository(LeadList::class);

        $lists = $repo->getGlobalLists();

        $segmentTest2Ref = $this->fixtures->getReference('segment-test-2');

        $this->assertArrayNotHasKey(
            $segmentTest2Ref->getId(),
            $lists,
            'Non-public lists should not be returned by the `getGlobalLists()` method.'
        );
    }
}
