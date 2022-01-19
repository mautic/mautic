<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadFieldData;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadListData;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData;
use Mautic\PageBundle\DataFixtures\ORM\LoadPageCategoryData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;

/**
 * These tests cover same tests like \Mautic\LeadBundle\Tests\Model\ListModelFunctionalTest.
 */
class ContactSegmentServiceFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ReferenceRepository
     */
    private $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = $this->loadFixtures([
            LoadCompanyData::class,
            LoadLeadListData::class,
            LoadLeadData::class,
            LoadLeadFieldData::class,
            LoadPageHitData::class,
            LoadSegmentsData::class,
            LoadPageCategoryData::class,
            LoadRoleData::class,
            LoadUserData::class,
        ], false)->getReferenceRepository();
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'lead_lists',
        ]);
    }

    public function testSegmentCountIsCorrect(): void
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = self::$container->get('mautic.lead.model.lead_segment_service');

        $segmentTest1Ref = $this->getReference('segment-test-1');
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest1Ref);
        $this->assertEquals(
            1,
            $segmentContacts[$segmentTest1Ref->getId()]['count'],
            'There should be 1 contacts in the segment-test-1 segment.'
        );

        $segmentTest2Ref = $this->getReference('segment-test-2');
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest2Ref);
        $this->assertEquals(
            4,
            $segmentContacts[$segmentTest2Ref->getId()]['count'],
            'There should be 4 contacts in the segment-test-2 segment.'
        );

        $segmentTest3Ref = $this->getReference('segment-test-3');
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest3Ref);
        $this->assertEquals(
            24,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be 24 contacts in the segment-test-3 segment'
        );

        $segmentTest4Ref = $this->getReference('segment-test-4');
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest4Ref);
        $this->assertEquals(
            1,
            $segmentContacts[$segmentTest4Ref->getId()]['count'],
            'There should be 1 contacts in the segment-test-4 segment.'
        );

        $segmentTest5Ref = $this->getReference('segment-test-5');
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest5Ref);
        $this->assertEquals(
            53,
            $segmentContacts[$segmentTest5Ref->getId()]['count'],
            'There should be 53 contacts in the segment-test-5 segment.'
        );

        $likePercentEndRef = $this->getReference('like-percent-end');
        $segmentContacts   = $contactSegmentService->getTotalLeadListLeadsCount($likePercentEndRef);
        $this->assertEquals(
            32,
            $segmentContacts[$likePercentEndRef->getId()]['count'],
            'There should be 32 contacts in the like-percent-end segment.'
        );

        $segmentTestWithoutFiltersRef = $this->getReference('segment-test-without-filters');
        $segmentContacts              = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestWithoutFiltersRef);
        $this->assertEquals(
            0,
            $segmentContacts[$segmentTestWithoutFiltersRef->getId()]['count'],
            'There should be 0 contacts in the segment-test-without-filters segment.'
        );

        $segmentTestIncludeMembershipWithFiltersRef = $this->getReference('segment-test-include-segment-with-filters');
        $segmentContacts                            = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestIncludeMembershipWithFiltersRef);
        $this->assertEquals(
            26,
            $segmentContacts[$segmentTestIncludeMembershipWithFiltersRef->getId()]['count'],
            'There should be 26 contacts in the segment-test-include-segment-with-filters segment. 24 from segment-test-3 that was not added yet plus 4 from segment-test-2 minus 2 for being in both = 26.'
        );

        $segmentTestExcludeMembershipWithFiltersRef = $this->getReference('segment-test-exclude-segment-with-filters');
        $segmentContacts                            = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestExcludeMembershipWithFiltersRef);
        $this->assertEquals(
            7,
            $segmentContacts[$segmentTestExcludeMembershipWithFiltersRef->getId()]['count'],
            'There should be 7 contacts in the segment-test-exclude-segment-with-filters segment. 8 that are in the US minus 1 that is in segment-test-3.'
        );

        $segmentTestIncludeMembershipWithoutFiltersRef = $this->getReference('segment-test-include-segment-without-filters');
        $segmentContacts                               = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestIncludeMembershipWithoutFiltersRef);
        $this->assertEquals(
            0,
            $segmentContacts[$segmentTestIncludeMembershipWithoutFiltersRef->getId()]['count'],
            'There should be 0 contacts as there is no one in segment-test-without-filters'
        );

        $segmentTestExcludeMembershipWithoutFiltersRef = $this->getReference('segment-test-exclude-segment-without-filters');
        $segmentContacts                               = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestExcludeMembershipWithoutFiltersRef);
        $this->assertEquals(
            11,
            $segmentContacts[$segmentTestExcludeMembershipWithoutFiltersRef->getId()]['count'],
            'There should be 11 contacts in the United Kingdom and 0 from segment-test-without-filters.'
        );

        $segmentTestIncludeMembershipMixedFiltersRef = $this->getReference('segment-test-include-segment-mixed-filters');
        $segmentContacts                             = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestIncludeMembershipMixedFiltersRef);
        $this->assertEquals(
            24,
            $segmentContacts[$segmentTestIncludeMembershipMixedFiltersRef->getId()]['count'],
            'There should be 24 contacts. 0 from segment-test-without-filters and 24 from segment-test-3.'
        );

        $segmentTestExcludeMembershipMixedFiltersRef = $this->getReference('segment-test-exclude-segment-mixed-filters');
        $segmentContacts                             = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestExcludeMembershipMixedFiltersRef);
        $this->assertEquals(
            30,
            $segmentContacts[$segmentTestExcludeMembershipMixedFiltersRef->getId()]['count'],
            'There should be 30 contacts. 0 from segment-test-without-filters and 30 from segment-test-3.'
        );

        $segmentTestMixedIncludeExcludeRef = $this->getReference('segment-test-mixed-include-exclude-filters');
        $segmentContacts                   = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestMixedIncludeExcludeRef);
        $this->assertEquals(
            8,
            $segmentContacts[$segmentTestMixedIncludeExcludeRef->getId()]['count'],
            'There should be 8 contacts. 32 from like-percent-end minus 24 from segment-test-3.'
        );

        $segmentTestManualMembership = $this->getReference('segment-test-manual-membership');
        $segmentContacts             = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestManualMembership);
        $this->assertEquals(
            12,
            $segmentContacts[$segmentTestManualMembership->getId()]['count'],
            'There should be 12 contacts. 11 in the United Kingdom plus 3 manually added minus 2 manually removed.'
        );

        $segmentTestIncludeMembershipManualMembersRef = $this->getReference('segment-test-include-segment-manual-members');
        $segmentContacts                              = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestIncludeMembershipManualMembersRef);
        $this->assertEquals(
            12,
            $segmentContacts[$segmentTestIncludeMembershipManualMembersRef->getId()]['count'],
            'There should be 12 contacts in the included segment-test-include-segment-manual-members segment'
        );

        $segmentTestExcludeMembershipManualMembersRef = $this->getReference('segment-test-exclude-segment-manual-members');
        $segmentContacts                              = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestExcludeMembershipManualMembersRef);
        $this->assertEquals(
            25,
            $segmentContacts[$segmentTestExcludeMembershipManualMembersRef->getId()]['count'],
            'There should be 25 contacts in the segment-test-exclude-segment-manual-members segment'
        );

        $segmentTestExcludeMembershipWithoutOtherFiltersRef = $this->getReference('segment-test-exclude-segment-without-other-filters');
        $segmentContacts                                    = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestExcludeMembershipWithoutOtherFiltersRef);
        $this->assertEquals(
            42,
            $segmentContacts[$segmentTestExcludeMembershipWithoutOtherFiltersRef->getId()]['count'],
            'There should be 42 contacts in the included segment-test-exclude-segment-without-other-filters segment'
        );

        $segmentTestIncludeWithUnrelatedManualRemovalRef = $this->getReference('segment-test-include-segment-with-unrelated-segment-manual-removal');
        $segmentContacts                                 = $contactSegmentService->getTotalLeadListLeadsCount($segmentTestIncludeWithUnrelatedManualRemovalRef);
        $this->assertEquals(
            11,
            $segmentContacts[$segmentTestIncludeWithUnrelatedManualRemovalRef->getId()]['count'],
            'There should be 11 contacts in the segment-test-include-segment-with-unrelated-segment-manual-removal segment where a contact has been manually removed form another list'
        );

        $segmentMembershipRegex = $this->getReference('segment-membership-regexp');
        $segmentContacts        = $contactSegmentService->getTotalLeadListLeadsCount($segmentMembershipRegex);
        $this->assertEquals(
            11,
            $segmentContacts[$segmentMembershipRegex->getId()]['count'],
            'There should be 11 contacts that match the regex with dayrep.com in it'
        );

        $segmentCompanyFields = $this->getReference('segment-company-only-fields');
        $segmentContacts      = $contactSegmentService->getTotalLeadListLeadsCount($segmentCompanyFields);
        $this->assertEquals(
            6,
            $segmentContacts[$segmentCompanyFields->getId()]['count'],
            'There should only be 6 in this segment (6 contacts belong to HostGator based in Houston)'
        );

        $segmentMembershipCompanyOnlyFields = $this->getReference('segment-including-segment-with-company-only-fields');
        $segmentContacts                    = $contactSegmentService->getTotalLeadListLeadsCount($segmentMembershipCompanyOnlyFields);
        $this->assertEquals(
            14,
            $segmentContacts[$segmentMembershipCompanyOnlyFields->getId()]['count'],
            'There should be 14 in this segment.'
        );

        $segmentMembershipCompanyOnlyFields = $this->getReference('name-is-not-equal-not-null-test');
        $segmentContacts                    = $contactSegmentService->getTotalLeadListLeadsCount($segmentMembershipCompanyOnlyFields);
        $this->assertEquals(
            54,
            $segmentContacts[$segmentMembershipCompanyOnlyFields->getId()]['count'],
            'There should be 54 in this segment. Check that contact with NULL firstname were added if error here'
        );
    }

    public function testSegmentRebuildCommand(): void
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = self::$container->get('mautic.lead.model.lead_segment_service');
        $segmentTest3Ref       = $this->getReference('segment-test-3');

        $this->runCommand('mautic:segments:update', [
            '-i'    => $segmentTest3Ref->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest3Ref);

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

        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentTest3Ref);

        $this->assertEquals(
            0,
            $segmentContacts[$segmentTest3Ref->getId()]['count'],
            'There should be no contacts in the segment-test-3 segment after removing contact titles and rebuilding from the command line.'
        );
    }

    private function getReference(string $name): LeadList
    {
        /** @var LeadList $reference */
        $reference = $this->fixtures->getReference($name);

        return $reference;
    }
}
