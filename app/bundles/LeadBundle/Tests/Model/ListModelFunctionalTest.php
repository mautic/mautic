<?php

namespace Mautic\LeadBundle\Tests;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;

class ListModelFunctionalTest extends MauticWebTestCase
{
    public function testSegmentCountIsCorrect()
    {
        $repo            = $this->em->getRepository(LeadList::class);
        $leadListRef     = $this->fixtures->getReference('lead-list');
        $segmentTest1Ref = $this->fixtures->getReference('segment-test-1');
        $segmentTest2Ref = $this->fixtures->getReference('segment-test-2');
        $segmentTest3Ref = $this->fixtures->getReference('segment-test-3');

        $segmentContacts = $repo->getLeadsByList([
            $leadListRef->getId(),
            $segmentTest1Ref->getId(),
            $segmentTest2Ref->getId(),
            $segmentTest3Ref->getId(),
        ], ['countOnly' => true]);

        $this->assertEquals(
            8,
            $segmentContacts[$leadListRef->getId()]['count'],
            'There should be 8 contacts in the lead-list segment.'
        );

        $this->assertEquals(
            1,
            $segmentContacts[$segmentTest1Ref->getId()]['count'],
            'There should be 1 contacts in the segment-test-1 segment.'
        );

        $this->assertEquals(
            4,
            $segmentContacts[$segmentTest2Ref->getId()]['count'],
            'There should be 4 contacts in the segment-test-2 segment.'
        );

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be 0 contacts in the segment-test-3 segment because the segment has not been built yet.'
        );
    }

    public function testPublicSegmentsInContactPreferences()
    {
        $repo = $this->em->getRepository(LeadList::class);

        $lists = $repo->getGlobalLists();

        $segmentTest2Ref = $this->fixtures->getReference('segment-test-2');

        $this->assertArrayNotHasKey(
            $segmentTest2Ref->getId(),
            $lists,
            'Non-public lists should not be returned by the `getGlobalLists()` method.'
        );
    }

    public function testSegmentRebuildCommand()
    {
        $repo            = $this->em->getRepository(LeadList::class);
        $segmentTest3Ref = $this->fixtures->getReference('segment-test-3');

        $this->runCommand('mautic:segments:update', [
            '-i'    => $segmentTest3Ref->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $repo->getLeadsByList([
            $segmentTest3Ref->getId(),
        ], ['countOnly' => true]);

        $this->assertEquals(
            24,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be 24 contacts in the segment-test-3 segment after rebuilding from the command line.'
        );

        // Remove the title from all contacts, rebuild the list, and check that list is updated
        $this->em->getConnection()->query(sprintf('UPDATE %sleads SET title = NULL;', MAUTIC_TABLE_PREFIX));

        $this->runCommand('mautic:segments:update', [
            '-i'    => $segmentTest3Ref->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $repo->getLeadsByList([
            $segmentTest3Ref->getId(),
        ], ['countOnly' => true]);

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be no contacts in the segment-test-3 segment after removing contact titles and rebuilding from the command line.'
        );
    }
}
