<?php

namespace Mautic\LeadBundle\Tests;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\LeadList;

class ListModelFunctionalTest extends MauticWebTestCase
{
    public function testSegmentCountIsCorrect()
    {
        $repo                                               = $this->em->getRepository(LeadList::class);
        $segmentTest1Ref                                    = $this->fixtures->getReference('segment-test-1');
        $segmentTest2Ref                                    = $this->fixtures->getReference('segment-test-2');
        $segmentTest3Ref                                    = $this->fixtures->getReference('segment-test-3');
        $segmentTest4Ref                                    = $this->fixtures->getReference('segment-test-4');
        $segmentTest5Ref                                    = $this->fixtures->getReference('segment-test-5');
        $likePercentEndRef                                  = $this->fixtures->getReference('like-percent-end');
        $segmentTestWithoutFiltersRef                       = $this->fixtures->getReference('segment-test-without-filters');
        $segmentTestIncludeMembershipWithFiltersRef         = $this->fixtures->getReference('segment-test-include-segment-with-filters');
        $segmentTestExcludeMembershipWithFiltersRef         = $this->fixtures->getReference('segment-test-exclude-segment-with-filters');
        $segmentTestIncludeMembershipWithoutFiltersRef      = $this->fixtures->getReference('segment-test-include-segment-without-filters');
        $segmentTestExcludeMembershipWithoutFiltersRef      = $this->fixtures->getReference('segment-test-exclude-segment-without-filters');
        $segmentTestIncludeMembershipMixedFiltersRef        = $this->fixtures->getReference('segment-test-include-segment-mixed-filters');
        $segmentTestExcludeMembershipMixedFiltersRef        = $this->fixtures->getReference('segment-test-exclude-segment-mixed-filters');
        $segmentTestMixedIncludeExcludeRef                  = $this->fixtures->getReference('segment-test-mixed-include-exclude-filters');
        $segmentTestManualMembership                        = $this->fixtures->getReference('segment-test-manual-membership');
        $segmentTestIncludeMembershipManualMembersRef       = $this->fixtures->getReference('segment-test-include-segment-manual-members');
        $segmentTestExcludeMembershipManualMembersRef       = $this->fixtures->getReference('segment-test-exclude-segment-manual-members');
        $segmentTestExcludeMembershipWithoutOtherFiltersRef = $this->fixtures->getReference('segment-test-exclude-segment-without-other-filters');
        $segmentTestIncludeWithUnrelatedManualRemovalRef    = $this->fixtures->getReference('segment-test-include-segment-with-unrelated-segment-manual-removal');

        // These expect filters to be part of the $lists passed to getLeadsByList so pass the entity
        $segmentContacts = $repo->getLeadsByList(
            [
                $segmentTest1Ref,
                $segmentTest2Ref,
                $segmentTest3Ref,
                $segmentTest4Ref,
                $segmentTest5Ref,
                $likePercentEndRef,
                $segmentTestWithoutFiltersRef,
                $segmentTestIncludeMembershipWithFiltersRef,
                $segmentTestExcludeMembershipWithFiltersRef,
                $segmentTestIncludeMembershipWithoutFiltersRef,
                $segmentTestExcludeMembershipWithoutFiltersRef,
                $segmentTestIncludeMembershipMixedFiltersRef,
                $segmentTestExcludeMembershipMixedFiltersRef,
                $segmentTestMixedIncludeExcludeRef,
                $segmentTestManualMembership,
                $segmentTestIncludeMembershipManualMembersRef,
                $segmentTestExcludeMembershipManualMembersRef,
                $segmentTestExcludeMembershipWithoutOtherFiltersRef,
                $segmentTestIncludeWithUnrelatedManualRemovalRef
            ],
            ['countOnly' => true]
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
            24,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be 24 contacts in the segment-test-3 segment'
        );

        $this->assertEquals(
            1,
            $segmentContacts[$segmentTest4Ref->getId()]['count'],
            'There should be 1 contacts in the segment-test-4 segment.'
        );

        $this->assertEquals(
            53,
            $segmentContacts[$segmentTest5Ref->getId()]['count'],
            'There should be 53 contacts in the segment-test-5 segment.'
        );

        $this->assertEquals(
            32,
            $segmentContacts[$likePercentEndRef->getId()]['count'],
            'There should be 32 contacts in the like-percent-end segment.'
        );

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTestWithoutFiltersRef->getId()]['count'],
            'There should be 0 contacts in the segment-test-without-filters segment.'
        );

        $this->assertEquals(
            26,
            $segmentContacts[$segmentTestIncludeMembershipWithFiltersRef->getId()]['count'],
            'There should be 26 contacts in the segment-test-include-segment-with-filters segment. 24 from segment-test-3 that was not added yet plus 4 from segment-test-2 minus 2 for being in both = 26.'
        );

        $this->assertEquals(
            7,
            $segmentContacts[$segmentTestExcludeMembershipWithFiltersRef->getId()]['count'],
            'There should be 7 contacts in the segment-test-exclude-segment-with-filters segment. 8 that are in the US minus 1 that is in segment-test-3.'
        );

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTestIncludeMembershipWithoutFiltersRef->getId()]['count'],
            'There should be 0 contacts as there is no one in segment-test-without-filters'
        );

        $this->assertEquals(
            11,
            $segmentContacts[$segmentTestExcludeMembershipWithoutFiltersRef->getId()]['count'],
            'There should be 11 contacts in the United Kingdom and 0 from segment-test-without-filters.'
        );

        $this->assertEquals(
            24,
            $segmentContacts[$segmentTestIncludeMembershipMixedFiltersRef->getId()]['count'],
            'There should be 24 contacts. 0 from segment-test-without-filters and 24 from segment-test-3.'
        );

        $this->assertEquals(
            30,
            $segmentContacts[$segmentTestExcludeMembershipMixedFiltersRef->getId()]['count'],
            'There should be 30 contacts. 0 from segment-test-without-filters and 30 from segment-test-3.'
        );

        $this->assertEquals(
            8,
            $segmentContacts[$segmentTestMixedIncludeExcludeRef->getId()]['count'],
            'There should be 8 contacts. 32 from like-percent-end minus 24 from segment-test-3.'
        );

        $this->assertEquals(
            12,
            $segmentContacts[$segmentTestManualMembership->getId()]['count'],
            'There should be 12 contacts. 11 in the United Kingdom plus 3 manually added minus 2 manually removed.'
        );

        $this->assertEquals(
            12,
            $segmentContacts[$segmentTestIncludeMembershipManualMembersRef->getId()]['count'],
            'There should be 12 contacts in the included segment-test-include-segment-manual-members segment'
        );

        $this->assertEquals(
            25,
            $segmentContacts[$segmentTestExcludeMembershipManualMembersRef->getId()]['count'],
            'There should be 25 contacts in the segment-test-exclude-segment-manual-members segment'
        );

        $this->assertEquals(
            42,
            $segmentContacts[$segmentTestExcludeMembershipWithoutOtherFiltersRef->getId()]['count'],
            'There should be 42 contacts in the included segment-test-exclude-segment-without-other-filters segment'
        );

        $this->assertEquals(
            26,
            $segmentContacts[$segmentTestIncludeWithUnrelatedManualRemovalRef->getId()]['count'],
            'There should be 26 contacts in the included segment-test-include-segment-with-filters segment where a contact has been manually removed form another list'
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
            $segmentTest3Ref,
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
            $segmentTest3Ref,
        ], ['countOnly' => true]);

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be no contacts in the segment-test-3 segment after removing contact titles and rebuilding from the command line.'
        );
    }
}
