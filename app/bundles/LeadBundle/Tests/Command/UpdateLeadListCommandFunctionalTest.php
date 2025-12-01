<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\Tag;
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

        Assert::assertSame(1, $output->getStatusCode());
        Assert::assertStringContainsString('Segment #999999 does not exist', $output->getDisplay());
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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, $getCommandParams($segment));

        /** @var LeadList $segment */
        $segment = $this->em->find(LeadList::class, $segment->getId());
        $assert($segment, $output->getDisplay());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            1,
            $leadListRepository->getLeadCount([$segment->getId()])
        );
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

        // But the last built date will not update if we limit how many contacts to process.
        // Also testing the timing option = 1.
        yield [
            fn (): array => ['--max-contacts' => 1, '--timing' => 1],
            function (LeadList $segment, string $output): void {
                Assert::assertEquals(
                    new \DateTime('2000-01-01 00:00:00'),
                    $segment->getLastBuiltDate()
                );
                Assert::assertNull($segment->getLastBuiltTime());
                Assert::assertStringContainsString('Total time:', $output);
                Assert::assertStringContainsString('seconds', $output);
            },
        ];
    }

    /**
     * @param int|string $expected
     * @param array<int> $addTagsToContact
     * @param array<int> $addTagsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testTagIncludeExclude(string $filter, $expected, array $addTagsToContact, array $addTagsToSegment): void
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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segment->getId()])
        );
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
     * @param int|string $expected
     * @param array<int> $addFieldsToContact
     * @param array<int> $addFieldsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testCustomFieldIncludeExclude(string $filter, $expected, array $addFieldsToContact, array $addFieldsToSegment): void
    {
        $fieldAlias = 'test_inc_ex_field';

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);

        $fields = $fieldModel->getLeadFieldCustomFields();
        Assert::assertEmpty($fields, 'There are no Custom Fields.');

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
        \assert($contactModel instanceof LeadModel);
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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segment->getId()])
        );
    }

    /**
     * @param int|string $expected
     * @param array<int> $addSegmentsToContact
     * @param array<int> $addSegmentsToSegment
     */
    #[DataProvider('provideIncludeExclude')]
    public function testSegmentIncludeExclude(string $filter, $expected, array $addSegmentsToContact, array $addSegmentsToSegment): void
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

        Assert::assertEquals($longTimeAgo, $segmentD->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segmentD->getId()])
        );
    }
}
