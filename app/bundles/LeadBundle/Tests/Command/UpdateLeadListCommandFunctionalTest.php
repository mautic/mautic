<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;

final class UpdateLeadListCommandFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected $useCleanupRollback = false; // This should be here, because test is changing DDL of the leads table.

    public function testFailWhenSegmentDoesNotExist(): void
    {
        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => 999999]);

        $this->assertSame(1, $output->getStatusCode());
        $this->assertStringContainsString('Segment #999999 does not exist', $output->getDisplay());
    }

    #[DataProvider('provider')]
    public function testCommandRebuildingAllSegments(callable $getCommandParams, callable $assert): void
    {
        $contact = new Lead();
        $contact->setEmail('halusky@bramborak.makovec');

        $segment = new LeadList();
        $segment->setName('Test segment');
        $segment->setPublicName('Test segment');
        $segment->setAlias('test-segment');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'halusky@bramborak.makovec',
                'display'  => null,
                'operator' => 'eq',
            ],
        ]);

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');

        // The last built date is set on pre persist to 2000-01-01 00:00:00.
        // Setting it 1 year ago so we could assert that it is updated after the command runs.
        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->persist($contact);
        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, $getCommandParams($segment));

        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segment->getId());
        $assert($segment, $output->getDisplay());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame(1, $leadListRepository->getLeadCount([$segment->getId()]));
    }

    /**
     * @return iterable<array<callable>>
     */
    public static function Provider(): iterable
    {
        // Test that all segments will be rebuilt with no params set.
        yield [
            fn (): array => [],
            function (LeadList $segment): void {
                Assert::assertGreaterThan(
                    new \DateTime('2000-01-01 00:00:00'),
                    $segment->getLastBuiltDate()
                );
                Assert::assertNotNull($segment->getLastBuiltTime());
            },
        ];

        // Test that it will work when we select a specific segment too.
        // Also testing the timing option = 0.
        yield [
            fn (LeadList $segment): array => ['--list-id' => $segment->getId()],
            function (LeadList $segment, string $output): void {
                Assert::assertGreaterThan(
                    new \DateTime('2000-01-01 00:00:00'),
                    $segment->getLastBuiltDate()
                );
                Assert::assertNotNull($segment->getLastBuiltTime());
                Assert::assertStringNotContainsString('Total time:', $output);
            },
        ];

        // When --max-contacts caps a run but all matching contacts are still processed,
        // the rebuild completes and last built date is updated.
        // Also testing the timing option = 1.
        yield [
            fn (): array => ['--max-contacts' => 1, '--timing' => 1],
            function (LeadList $segment, string $output): void {
                // Only one contact matches; the cap is reached but the rebuild is still complete.
                Assert::assertGreaterThan(
                    new \DateTime('2000-01-01 00:00:00'),
                    $segment->getLastBuiltDate()
                );
                Assert::assertNotNull($segment->getLastBuiltTime());
                Assert::assertStringContainsString('Total time:', $output);
                Assert::assertStringContainsString('seconds', $output);
            },
        ];
    }

    public function testMaxContactsPartialRebuildKeepsBuildingAndUpdatesCountCache(): void
    {
        $contacts = [];
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
            $contacts[] = $contact;
        }

        $segment = new LeadList();
        $segment->setName('Partial rebuild segment');
        $segment->setPublicName('Partial rebuild segment');
        $segment->setAlias('partial-rebuild-segment');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'example.com',
                'display'  => null,
                'operator' => 'like',
            ],
        ]);

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');
        $segment->setDateModified(new \DateTime());
        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->persist($segment);
        $this->em->flush();
        $segmentId = $segment->getId();
        $this->em->clear();

        $this->assertTrue(
            $this->em->find(LeadList::class, $segmentId)->needsRebuild(),
            'Segment should show as building before the partial rebuild'
        );

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertNotInstanceOf(\DateTimeInterface::class, $segment->getLastBuiltDate());
        $this->assertTrue($segment->needsRebuild());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);
        $this->assertSame(1, $leadListRepository->getLeadCount([$segmentId]));

        /** @var SegmentCountCacheHelper $segmentCountCacheHelper */
        $segmentCountCacheHelper = self::getContainer()->get(SegmentCountCacheHelper::class);
        $this->assertSame(1, $segmentCountCacheHelper->getSegmentContactCount($segmentId));

        // Drain the remaining contacts; the final capped run should mark the rebuild finished.
        $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);
        $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);

        $this->em->clear();
        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertGreaterThan($longTimeAgo, $segment->getLastBuiltDate());
        $this->assertNotNull($segment->getLastBuiltTime());
        $this->assertSame(3, $leadListRepository->getLeadCount([$segmentId]));
        $this->assertSame(3, $segmentCountCacheHelper->getSegmentContactCount($segmentId));
    }

    public function testMaxContactsPartialOrphanRemovalKeepsBuildingAndUpdatesCountCache(): void
    {
        $contacts = [];
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
            $contacts[] = $contact;
        }

        $segment = new LeadList();
        $segment->setName('Orphan removal segment');
        $segment->setPublicName('Orphan removal segment');
        $segment->setAlias('orphan-removal-segment');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'one@example.com',
                'display'  => null,
                'operator' => 'eq',
            ],
        ]);

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');
        $segment->setDateModified(new \DateTime());
        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->persist($segment);
        $this->em->flush();

        foreach ($contacts as $contact) {
            $this->createListLead($segment, $contact);
        }

        $this->em->flush();
        $segmentId = $segment->getId();
        $this->em->clear();

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);
        /** @var SegmentCountCacheHelper $segmentCountCacheHelper */
        $segmentCountCacheHelper = self::getContainer()->get(SegmentCountCacheHelper::class);

        $this->assertSame(3, $leadListRepository->getLeadCount([$segmentId]));

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertNotInstanceOf(\DateTimeInterface::class, $segment->getLastBuiltDate());
        $this->assertTrue($segment->needsRebuild());
        $this->assertSame(2, $leadListRepository->getLeadCount([$segmentId]));
        $this->assertSame(2, $segmentCountCacheHelper->getSegmentContactCount($segmentId));

        $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);

        $this->em->clear();
        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertGreaterThan($longTimeAgo, $segment->getLastBuiltDate());
        $this->assertNotNull($segment->getLastBuiltTime());
        $this->assertSame(1, $leadListRepository->getLeadCount([$segmentId]));
        $this->assertSame(1, $segmentCountCacheHelper->getSegmentContactCount($segmentId));
    }

    public function testMaxContactsPartialDependentSegmentDoesNotMarkCompleteEarly(): void
    {
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }

        $baseSegment = new LeadList();
        $baseSegment->setName('Base segment');
        $baseSegment->setPublicName('Base segment');
        $baseSegment->setAlias('base-segment');
        $baseSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'example.com',
                'display'  => null,
                'operator' => 'like',
            ],
        ]);

        $this->em->persist($baseSegment);
        $this->em->flush();
        $baseSegmentId = $baseSegment->getId();

        $dependentSegment = new LeadList();
        $dependentSegment->setName('Dependent segment');
        $dependentSegment->setPublicName('Dependent segment');
        $dependentSegment->setAlias('dependent-segment');
        $dependentSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [$baseSegmentId],
                'display'  => null,
                'operator' => 'in',
            ],
        ]);

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');
        $dependentSegment->setDateModified(new \DateTime());
        $dependentSegment->setLastBuiltDate($longTimeAgo);
        $baseSegment->setDateModified(new \DateTime());
        $baseSegment->setLastBuiltDate($longTimeAgo);

        $this->em->persist($dependentSegment);
        $this->em->persist($baseSegment);
        $this->em->flush();
        $dependentSegmentId = $dependentSegment->getId();
        $this->em->clear();

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $dependentSegmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $baseSegment */
        $baseSegment = $this->em->find(LeadList::class, $baseSegmentId);
        /** @var LeadList $dependentSegment */
        $dependentSegment = $this->em->find(LeadList::class, $dependentSegmentId);

        $this->assertNotInstanceOf(\DateTimeInterface::class, $baseSegment->getLastBuiltDate());
        $this->assertTrue($baseSegment->needsRebuild());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $dependentSegment->getLastBuiltDate());
        $this->assertTrue($dependentSegment->needsRebuild());
    }

    public function testMaxContactsDependentSegmentNotMarkedCompleteWhenSingleRunLeavesBasePartial(): void
    {
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }

        $baseSegment = new LeadList();
        $baseSegment->setName('Base segment with pending contacts');
        $baseSegment->setPublicName('Base segment with pending contacts');
        $baseSegment->setAlias('base-segment-pending-'.uniqid());
        $baseSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'example.com',
                'display'  => null,
                'operator' => 'like',
            ],
        ]);

        $this->em->persist($baseSegment);
        $this->em->flush();
        $baseSegmentId = $baseSegment->getId();

        $dependentSegment = new LeadList();
        $dependentSegment->setName('Dependent segment with pending base');
        $dependentSegment->setPublicName('Dependent segment with pending base');
        $dependentSegment->setAlias('dependent-segment-pending-'.uniqid());
        $dependentSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [$baseSegmentId],
                'display'  => null,
                'operator' => 'in',
            ],
        ]);

        $this->em->persist($dependentSegment);
        $this->em->flush();
        $dependentSegmentId = $dependentSegment->getId();
        $this->em->clear();

        // Full build first: both segments become complete.
        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id' => $dependentSegmentId,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $baseSegment */
        $baseSegment = $this->em->find(LeadList::class, $baseSegmentId);
        /** @var LeadList $dependentSegment */
        $dependentSegment = $this->em->find(LeadList::class, $dependentSegmentId);
        $this->assertFalse($baseSegment->needsRebuild(), 'Base segment should be complete after a full build');
        $this->assertFalse($dependentSegment->needsRebuild(), 'Dependent segment should be complete after a full build');

        // New matching contacts leave the previously complete base with pending work.
        foreach (['four@example.com', 'five@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }
        $this->em->flush();
        $this->em->clear();

        // One capped run on the dependent segment: the base is rebuilt first but
        // stays incomplete, so the dependent must not be marked complete either.
        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $dependentSegmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $baseSegment */
        $baseSegment = $this->em->find(LeadList::class, $baseSegmentId);
        /** @var LeadList $dependentSegment */
        $dependentSegment = $this->em->find(LeadList::class, $dependentSegmentId);

        $this->assertNotInstanceOf(\DateTimeInterface::class, $baseSegment->getLastBuiltDate());
        $this->assertTrue($baseSegment->needsRebuild());
        $this->assertNotInstanceOf(\DateTimeInterface::class, $dependentSegment->getLastBuiltDate());
        $this->assertTrue($dependentSegment->needsRebuild());
    }

    public function testMaxContactsPartialRebuildKeepsPreviouslyCompleteSegmentBuilding(): void
    {
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }

        $segment = new LeadList();
        $segment->setName('Previously complete segment');
        $segment->setPublicName('Previously complete segment');
        $segment->setAlias('previously-complete-segment-'.uniqid());
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'example.com',
                'display'  => null,
                'operator' => 'like',
            ],
        ]);

        $this->em->persist($segment);
        $this->em->flush();
        $segmentId = $segment->getId();

        $lastBuiltDate = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            new \DateTime('-1 day')->format('Y-m-d H:i:s')
        );
        $segment->setLastBuiltDate($lastBuiltDate);
        $segment->setDateModified(new \DateTime('-1 week'));
        $segment->setLastBuiltTime(1.0);

        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => $email]);
            $this->createListLead($segment, $contact);
        }

        $this->em->persist($segment);
        $this->em->flush();
        $this->em->clear();

        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        $this->assertFalse($segment->needsRebuild(), 'Segment should appear complete before new contacts arrive');

        foreach (['four@example.com', 'five@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }
        $this->em->flush();
        $this->em->clear();

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $segmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segmentId);
        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame(4, $leadListRepository->getLeadCount([$segmentId]));
        $this->assertNotInstanceOf(\DateTimeInterface::class, $segment->getLastBuiltDate());
        $this->assertTrue(
            $segment->needsRebuild(),
            'Segment should keep showing as building while capped runs still have contacts to process'
        );
    }

    public function testMaxContactsPartialDependentSegmentStaysBuildingWhenPreviouslyCompleteBaseHasPendingWork(): void
    {
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }

        $baseSegment = new LeadList();
        $baseSegment->setName('Previously complete base segment');
        $baseSegment->setPublicName('Previously complete base segment');
        $baseSegment->setAlias('previously-complete-base-'.uniqid());
        $baseSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'example.com',
                'display'  => null,
                'operator' => 'like',
            ],
        ]);

        $this->em->persist($baseSegment);
        $this->em->flush();
        $baseSegmentId = $baseSegment->getId();

        $dependentSegment = new LeadList();
        $dependentSegment->setName('Previously complete dependent segment');
        $dependentSegment->setPublicName('Previously complete dependent segment');
        $dependentSegment->setAlias('previously-complete-dependent-'.uniqid());
        $dependentSegment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'leadlist',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [$baseSegmentId],
                'display'  => null,
                'operator' => 'in',
            ],
        ]);

        $lastBuiltDate = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            new \DateTime('-1 day')->format('Y-m-d H:i:s')
        );
        $dateModified  = new \DateTime('-1 week');

        foreach ([$baseSegment, $dependentSegment] as $listSegment) {
            $listSegment->setLastBuiltDate($lastBuiltDate);
            $listSegment->setDateModified($dateModified);
            $listSegment->setLastBuiltTime(1.0);
        }

        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $email) {
            $contact = $this->em->getRepository(Lead::class)->findOneBy(['email' => $email]);
            $this->createListLead($baseSegment, $contact);
            $this->createListLead($dependentSegment, $contact);
        }

        $this->em->persist($dependentSegment);
        $this->em->persist($baseSegment);
        $this->em->flush();
        $dependentSegmentId = $dependentSegment->getId();
        $this->em->clear();

        foreach (['four@example.com', 'five@example.com'] as $email) {
            $contact = new Lead();
            $contact->setEmail($email);
            $this->em->persist($contact);
        }
        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $baseSegmentId,
            '--max-contacts' => 1,
        ]);

        $this->em->clear();
        /** @var LeadList $baseSegment */
        $baseSegment = $this->em->find(LeadList::class, $baseSegmentId);
        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame(4, $leadListRepository->getLeadCount([$baseSegmentId]));
        $this->assertNotInstanceOf(\DateTimeInterface::class, $baseSegment->getLastBuiltDate());
        $this->assertTrue(
            $baseSegment->needsRebuild(),
            'Base segment should appear incomplete to dependent segment guards after a partial capped rebuild'
        );

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, [
            '--list-id'      => $dependentSegmentId,
            '--max-contacts' => 1,
        ]);
        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        $this->em->clear();
        /** @var LeadList $dependentSegment */
        $dependentSegment = $this->em->find(LeadList::class, $dependentSegmentId);

        $this->assertNotInstanceOf(\DateTimeInterface::class, $dependentSegment->getLastBuiltDate());
        $this->assertTrue(
            $dependentSegment->needsRebuild(),
            'Dependent segment should keep showing as building while capped rebuild work remains'
        );
    }

    /**
     * @param array<int> $addTagsToContact
     * @param array<int> $addTagsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testTagIncludeExclude(string $filter, int $expected, array $addTagsToContact, array $addTagsToSegment): void
    {
        $tag1 = new Tag('tag1');
        $tag2 = new Tag('tag2');
        $tag3 = new Tag('tag3');

        $this->em->persist($tag1);
        $this->em->persist($tag2);
        $this->em->persist($tag3);
        $this->em->flush();

        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');

        if (in_array(1, $addTagsToContact, true)) {
            $contact->addTag($tag1);
        }

        if (in_array(2, $addTagsToContact, true)) {
            $contact->addTag($tag2);
        }

        if (in_array(3, $addTagsToContact, true)) {
            $contact->addTag($tag3);
        }

        $tagSegment = [];

        if (in_array(1, $addTagsToSegment, true)) {
            $tagSegment[] = $tag1->getId();
        }

        if (in_array(2, $addTagsToSegment, true)) {
            $tagSegment[] = $tag2->getId();
        }

        if (in_array(3, $addTagsToSegment, true)) {
            $tagSegment[] = $tag3->getId();
        }

        $segment = $this->createSegment(
            'test-segment',
            [
                [
                    'glue'     => 'and',
                    'field'    => 'tags',
                    'object'   => 'lead',
                    'type'     => 'tags',
                    'filter'   => $tagSegment,
                    'display'  => null,
                    'operator' => $filter,
                ],
            ]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');

        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, $leadListRepository->getLeadCount([$segment->getId()]));
    }

    public static function provideIncludeExclude(): \Generator
    {
        yield 'include any with match' => [OperatorOptions::INCLUDING_ANY, 1, [1, 2], [1, 2, 3]];
        yield 'include any no match' => [OperatorOptions::INCLUDING_ANY, 0, [1, 2], [3]];
        yield 'exclude any with match' => [OperatorOptions::EXCLUDING_ANY, 0, [1, 2], [1, 2, 3]];
        yield 'exclude any no match' => [OperatorOptions::EXCLUDING_ANY, 1, [2], [1, 3]];
        yield 'include all no match' => [OperatorOptions::INCLUDING_ALL, 0, [1, 2], [1, 2, 3]];
        yield 'include all with match' => [OperatorOptions::INCLUDING_ALL, 1, [1, 3], [1, 3]];
        yield 'exclude all no match' => [OperatorOptions::EXCLUDING_ALL, 1, [1, 2], [1, 2, 3]];
        yield 'exclude all with match' => [OperatorOptions::EXCLUDING_ALL, 0, [1, 3], [1, 3]];
    }

    /**
     * @param array<int> $addFieldsToContact
     * @param array<int> $addFieldsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testCustomFieldIncludeExclude(string $filter, int $expected, array $addFieldsToContact, array $addFieldsToSegment): void
    {
        $fieldAlias = 'test_inc_ex_field';

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);

        $fields = $fieldModel->getLeadFieldCustomFields();
        $this->assertEmpty($fields, 'There are no Custom Fields.');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias($fieldAlias)
            ->setType('multiselect')
            ->setObject('lead')
            ->setProperties([
                'list' => [
                    [
                        'label' => 'Halusky',
                        'value' => 'halusky',
                    ],
                    [
                        'label' => 'Bramborak',
                        'value' => 'bramborak',
                    ],
                    [
                        'label' => 'Makovec',
                        'value' => 'makovec',
                    ],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');

        $contactValue = [];
        if (in_array(1, $addFieldsToContact, true)) {
            $contactValue[] = 'halusky';
        }

        if (in_array(2, $addFieldsToContact, true)) {
            $contactValue[] = 'bramborak';
        }

        if (in_array(3, $addFieldsToContact, true)) {
            $contactValue[] = 'makovec';
        }

        $contact->addUpdatedField($fieldAlias, $contactValue);
        $contactModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $contactModel);
        $contactModel->saveEntity($contact);

        $segmentValue = [];

        if (in_array(1, $addFieldsToSegment, true)) {
            $segmentValue[] = 'halusky';
        }

        if (in_array(2, $addFieldsToSegment, true)) {
            $segmentValue[] = 'bramborak';
        }

        if (in_array(3, $addFieldsToSegment, true)) {
            $segmentValue[] = 'makovec';
        }

        $segment = $this->createSegment(
            'test-segment',
            [
                [
                    'glue'     => 'and',
                    'field'    => $fieldAlias,
                    'object'   => 'lead',
                    'type'     => 'multiselect',
                    'filter'   => $segmentValue,
                    'display'  => null,
                    'operator' => $filter,
                ],
            ]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');

        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, $leadListRepository->getLeadCount([$segment->getId()]));
    }

    /**
     * @param array<int> $addFieldsToSegment
     */
    #[DataProvider('provideSingleIncludeExclude')]
    public function testCustomFieldSelectIncludeExclude(string $filter, int $expected, int $addFieldToContact, array $addFieldsToSegment): void
    {
        $fieldAlias = 'test_inc_ex_single_field';

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);

        $fields = $fieldModel->getLeadFieldCustomFields();
        $this->assertEmpty($fields, 'There are no Custom Fields.');

        // Add field.
        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias($fieldAlias)
            ->setType('select')
            ->setObject('lead')
            ->setProperties([
                'list' => [
                    [
                        'label' => 'Halusky',
                        'value' => 'halusky',
                    ],
                    [
                        'label' => 'Bramborak',
                        'value' => 'bramborak',
                    ],
                    [
                        'label' => 'Makovec',
                        'value' => 'makovec',
                    ],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');

        $contactValue = null;
        if (1 === $addFieldToContact) {
            $contactValue = 'halusky';
        }

        if (2 === $addFieldToContact) {
            $contactValue = 'bramborak';
        }

        if (3 === $addFieldToContact) {
            $contactValue = 'makovec';
        }

        $contact->addUpdatedField($fieldAlias, $contactValue);
        $contactModel = self::getContainer()->get(LeadModel::class);
        $this->assertInstanceOf(LeadModel::class, $contactModel);
        $contactModel->saveEntity($contact);

        $segmentValue = [];

        if (in_array(1, $addFieldsToSegment, true)) {
            $segmentValue[] = 'halusky';
        }

        if (in_array(2, $addFieldsToSegment, true)) {
            $segmentValue[] = 'bramborak';
        }

        if (in_array(3, $addFieldsToSegment, true)) {
            $segmentValue[] = 'makovec';
        }

        $segment = $this->createSegment(
            'test-segment',
            [
                [
                    'glue'     => 'and',
                    'field'    => $fieldAlias,
                    'object'   => 'lead',
                    'type'     => 'select',
                    'filter'   => $segmentValue,
                    'display'  => null,
                    'operator' => $filter,
                ],
            ]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');

        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, $leadListRepository->getLeadCount([$segment->getId()]));
    }

    public static function provideSingleIncludeExclude(): \Generator
    {
        yield 'include any with match' => [OperatorOptions::INCLUDING_ANY, 1, 1, [1, 2, 3]];
        yield 'include any no match' => [OperatorOptions::INCLUDING_ANY, 0, 2, [1, 3]];
        yield 'exclude any with match' => [OperatorOptions::EXCLUDING_ANY, 0, 1, [1, 2, 3]];
        yield 'exclude any no match' => [OperatorOptions::EXCLUDING_ANY, 1, 2, [1, 3]];
        yield 'include all no match' => [OperatorOptions::INCLUDING_ALL, 0, 1, [1, 2, 3]];
        yield 'include all no match multiple' => [OperatorOptions::INCLUDING_ALL, 0, 2, [1, 3]]; // Multiple values can't match "in_all" with single value
        yield 'include all with match' => [OperatorOptions::INCLUDING_ALL, 1, 1, [1]];
        yield 'include all with match multiple' => [OperatorOptions::INCLUDING_ALL, 0, 1, [1, 2]]; // Multiple values can't match "in_all" with single value
        yield 'exclude all no match' => [OperatorOptions::EXCLUDING_ALL, 1, 1, [1, 2, 3]];
        yield 'exclude all no match multiple' => [OperatorOptions::EXCLUDING_ALL, 1, 1, [2, 3]]; // Multiple values always match "!in_all" with single value
        yield 'exclude all with match' => [OperatorOptions::EXCLUDING_ALL, 0, 1, [1]];
        yield 'exclude all with match multiple' => [OperatorOptions::EXCLUDING_ALL, 1, 1, [1, 2]]; // Multiple values always match "!in_all" with single value
    }

    /**
     * @param array<int> $addFieldsToSegment
     */
    #[DataProvider('provideSingleIncludeExclude')]
    public function testCompanyCustomFieldSelectIncludeExclude(string $filter, int $expected, int $addFieldToCompany, array $addFieldsToSegment): void
    {
        $fieldAlias = 'test_company_inc_ex_single_field';

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);

        $leadField = new LeadField();
        $leadField->setName('Test Company Field')
            ->setAlias($fieldAlias)
            ->setType('select')
            ->setObject('company')
            ->setProperties([
                'list' => [
                    ['label' => 'Halusky', 'value' => 'halusky'],
                    ['label' => 'Bramborak', 'value' => 'bramborak'],
                    ['label' => 'Makovec', 'value' => 'makovec'],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $companyValue = match ($addFieldToCompany) {
            1       => 'halusky',
            2       => 'bramborak',
            3       => 'makovec',
            default => null,
        };

        $company = new Company();
        $company->setName('Test Company');
        $company->addUpdatedField($fieldAlias, $companyValue);

        $companyModel = self::getContainer()->get(CompanyModel::class);
        $this->assertInstanceOf(CompanyModel::class, $companyModel);
        $companyModel->saveEntity($company);

        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');
        $this->em->flush();

        $this->createPrimaryCompanyForLead($contact, $company);

        $segmentValue = [];
        if (in_array(1, $addFieldsToSegment, true)) {
            $segmentValue[] = 'halusky';
        }
        if (in_array(2, $addFieldsToSegment, true)) {
            $segmentValue[] = 'bramborak';
        }
        if (in_array(3, $addFieldsToSegment, true)) {
            $segmentValue[] = 'makovec';
        }

        $segment = $this->createSegment(
            'test-segment',
            [[
                'glue'     => 'and',
                'field'    => $fieldAlias,
                'object'   => 'company',
                'type'     => 'select',
                'filter'   => $segmentValue,
                'display'  => null,
                'operator' => $filter,
            ]]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');
        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, $leadListRepository->getLeadCount([$segment->getId()]));
    }

    /**
     * @param array<int> $addFieldsToCompany
     * @param array<int> $addFieldsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testCompanyCustomFieldIncludeExclude(string $filter, int $expected, array $addFieldsToCompany, array $addFieldsToSegment): void
    {
        $fieldAlias = 'test_company_inc_ex_field';

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);

        $leadField = new LeadField();
        $leadField->setName('Test Company Field')
            ->setAlias($fieldAlias)
            ->setType('multiselect')
            ->setObject('company')
            ->setProperties([
                'list' => [
                    ['label' => 'Halusky', 'value' => 'halusky'],
                    ['label' => 'Bramborak', 'value' => 'bramborak'],
                    ['label' => 'Makovec', 'value' => 'makovec'],
                ],
            ]);
        $fieldModel->saveEntity($leadField);

        $this->em->flush();

        $companyValue = [];
        if (in_array(1, $addFieldsToCompany, true)) {
            $companyValue[] = 'halusky';
        }
        if (in_array(2, $addFieldsToCompany, true)) {
            $companyValue[] = 'bramborak';
        }
        if (in_array(3, $addFieldsToCompany, true)) {
            $companyValue[] = 'makovec';
        }

        $company = new Company();
        $company->setName('Test Company');
        $company->addUpdatedField($fieldAlias, $companyValue);

        $companyModel = self::getContainer()->get(CompanyModel::class);
        $this->assertInstanceOf(CompanyModel::class, $companyModel);
        $companyModel->saveEntity($company);

        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');
        $this->em->flush();

        $this->createPrimaryCompanyForLead($contact, $company);

        $segmentValue = [];
        if (in_array(1, $addFieldsToSegment, true)) {
            $segmentValue[] = 'halusky';
        }
        if (in_array(2, $addFieldsToSegment, true)) {
            $segmentValue[] = 'bramborak';
        }
        if (in_array(3, $addFieldsToSegment, true)) {
            $segmentValue[] = 'makovec';
        }

        $segment = $this->createSegment(
            'test-segment',
            [[
                'glue'     => 'and',
                'field'    => $fieldAlias,
                'object'   => 'company',
                'type'     => 'multiselect',
                'filter'   => $segmentValue,
                'display'  => null,
                'operator' => $filter,
            ]]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');
        $segment->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, (int) $leadListRepository->getLeadCount([$segment->getId()]));
    }

    /**
     * @param array<int> $addSegmentsToContact
     * @param array<int> $addSegmentsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testSegmentIncludeExclude(string $filter, int $expected, array $addSegmentsToContact, array $addSegmentsToSegment): void
    {
        $contact = $this->createLead('First name', emailId: 'halusky@bramborak.makovec');

        $segmentA = $this->createSegment('A', []);
        $segmentB = $this->createSegment('B', []);
        $segmentC = $this->createSegment('C', []);

        $this->em->flush();

        if (in_array(1, $addSegmentsToContact, true)) {
            $this->createListLead($segmentA, $contact);
        }

        if (in_array(2, $addSegmentsToContact, true)) {
            $this->createListLead($segmentB, $contact);
        }

        if (in_array(3, $addSegmentsToContact, true)) {
            $this->createListLead($segmentC, $contact);
        }

        $filteredSegments = [];

        if (in_array(1, $addSegmentsToSegment, true)) {
            $filteredSegments[] = $segmentA->getId();
        }

        if (in_array(2, $addSegmentsToSegment, true)) {
            $filteredSegments[] = $segmentB->getId();
        }

        if (in_array(3, $addSegmentsToSegment, true)) {
            $filteredSegments[] = $segmentC->getId();
        }

        $segmentD = $this->createSegment(
            'D',
            [
                [
                    'glue'     => 'and',
                    'field'    => 'leadlist',
                    'object'   => 'lead',
                    'type'     => 'leadlist',
                    'filter'   => $filteredSegments,
                    'display'  => null,
                    'operator' => $filter,
                ],
            ]
        );

        $longTimeAgo = new \DateTime('2000-01-01 00:00:00');

        $segmentD->setLastBuiltDate($longTimeAgo);

        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($longTimeAgo, $segmentD->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        $this->assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        $this->assertSame($expected, $leadListRepository->getLeadCount([$segmentD->getId()]));
    }
}
