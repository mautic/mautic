<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Command\UpdateSegmentsCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\SegmentCompany;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Command\Command;

final class UpdateSegmentsCommandFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected $useCleanupRollback = false;

    public function testFailWhenLeadSegmentDoesNotExist(): void
    {
        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--list-id' => 999999]);

        Assert::assertSame(1, $output->getStatusCode());
        Assert::assertStringContainsString('Segment #999999 does not exist', $output->getDisplay());
    }

    #[DataProvider('provider')]
    public function testCommandRebuildingAllLeadSegments(callable $getCommandParams, callable $assert): void
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

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, $getCommandParams($segment));

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

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segment->getId()])
        );
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
        Assert::assertEmpty($fields, 'There are no Custom Fields.');

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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segment->getId()])
        );
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

        $companyModel = self::getContainer()->get(\Mautic\LeadBundle\Model\CompanyModel::class);
        $this->assertInstanceOf(\Mautic\LeadBundle\Model\CompanyModel::class, $companyModel);
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

        Assert::assertEquals($longTimeAgo, $segment->getLastBuiltDate());

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame($expected, $leadListRepository->getLeadCount([$segment->getId()]));
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

        $companyModel = self::getContainer()->get(\Mautic\LeadBundle\Model\CompanyModel::class);
        $this->assertInstanceOf(\Mautic\LeadBundle\Model\CompanyModel::class, $companyModel);
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

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            (int) $expected,
            (int) $leadListRepository->getLeadCount([$segment->getId()])
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

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME);

        Assert::assertSame(Command::SUCCESS, $output->getStatusCode());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        Assert::assertSame(
            $expected,
            $leadListRepository->getLeadCount([$segmentD->getId()])
        );
    }

    public function testLeadSegmentWithCompanySegmentMembershipFilter(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', emailId: 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', emailId: 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', emailId: 'leadthree@mautic.com');
        $leadFour  = $this->createLead('Braw Doe', emailId: 'leadfour@mautic.com');

        $this->createCompanyLead($companyGlobo, $leadOne, true);
        $this->createCompanyLead($companyGlobo, $leadTwo, true);
        $this->createCompanyLead($companySbt, $leadThree, true);
        $this->createCompanyLead($companySbt, $leadFour, true);

        $companySegmentOne = $this->createCompanySegment('Test Company Segment 1', 'test_comp_segment');

        // globo added in Company Segment 1
        $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);

        $filtersToCompanySegment  = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$companySegmentOne->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];

        // globo will be added in cs2 after company segment command
        $companySegmentTwo = $this->createCompanySegment('Test Company Segment 2', 'test_comp_segment2', true, $filtersToCompanySegment);

        $filtersToLeadSegment = [
            [
                'glue'       => 'and',
                'operator'   => '!=',
                'properties' => [
                    'filter' => 'asdasdaadasd',
                ],
                'field'  => 'address1',
                'type'   => 'text',
                'object' => 'lead',
            ],
            [
                'glue'       => 'and',
                'operator'   => '!in',
                'properties' => [
                    'filter' => [$companySegmentTwo->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];

        $leadSegmentTwo = $this->createSegment('Test Segment 2', $filtersToLeadSegment);

        $this->em->flush();
        $this->em->clear();

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        // Before segment update, no leads should be in the segment
        Assert::assertSame(0, $leadListRepository->getLeadCount([$leadSegmentTwo->getId()]));

        // Run unified command - processes company segments first, then lead segments
        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        Assert::assertStringContainsString('2 total contact(s) to be added', $output->getDisplay());

        // After update, 2 leads should be in the segment (leadThree and leadFour from SBT which is NOT in Company Segment 2)
        Assert::assertSame(2, $leadListRepository->getLeadCount([$leadSegmentTwo->getId()]));
    }

    public function testLeadSegmentWithCompanySegmentEmptyFilter(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', emailId: 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', emailId: 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', emailId: 'leadthree@mautic.com');
        $leadFour  = $this->createLead('Braw Doe', emailId: 'leadfour@mautic.com');

        $this->createCompanyLead($companyGlobo, $leadOne, true);
        $this->createCompanyLead($companyGlobo, $leadTwo, true);
        $this->createCompanyLead($companySbt, $leadThree, true);
        $this->createCompanyLead($companySbt, $leadFour, true);

        $companySegmentOne = $this->createCompanySegment('Test Company Segment 1', 'test_comp_segment');

        // globo added in Company Segment 1
        $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);

        $filtersToLeadSegment = [
            [
                'glue'       => 'and',
                'operator'   => 'empty',
                'field'      => 'company_segments',
                'type'       => 'company_segments',
                'object'     => 'company_segments',
            ],
        ];

        $leadSegmentOne = $this->createSegment('Test Segment 1', $filtersToLeadSegment);

        $this->em->flush();
        $this->em->clear();

        // Run lead segment update command
        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        Assert::assertStringContainsString('2 total contact(s) to be added', $output->getDisplay());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        // 2 leads should be in the segment (leadThree and leadFour from SBT which has no company segment)
        Assert::assertSame(2, $leadListRepository->getLeadCount([$leadSegmentOne->getId()]));
    }

    public function testLeadSegmentWithCompanySegmentNotEmptyFilter(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', emailId: 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', emailId: 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', emailId: 'leadthree@mautic.com');
        $leadFour  = $this->createLead('Braw Doe', emailId: 'leadfour@mautic.com');

        $this->createCompanyLead($companyGlobo, $leadOne, true);
        $this->createCompanyLead($companySbt, $leadThree, true);
        $this->createCompanyLead($companySbt, $leadFour, true);

        $companySegmentGlobo  = $this->createCompanySegment('Test Company Segment globo', 'test_comp_segment_globo');
        $companySegmentSbt    = $this->createCompanySegment('Test Company Segment Sbt', 'test_comp_segment_sbt');
        $companySegmentRecord = $this->createCompanySegment('Test Company Segment Record', 'test_comp_segment_record');

        // Add companies to segments
        $this->addCompanyToCompanySegment($companyGlobo, $companySegmentGlobo);
        $this->addCompanyToCompanySegment($companySbt, $companySegmentSbt);
        $this->addCompanyToCompanySegment($companyRecord, $companySegmentRecord);

        $filtersToLeadSegment = [
            [
                'glue'       => 'and',
                'operator'   => '!empty',
                'field'      => 'company_segments',
                'type'       => 'company_segments',
                'object'     => 'company_segments',
            ],
        ];

        $leadSegmentTwo = $this->createSegment('Test Segment all not empty', $filtersToLeadSegment);

        $this->em->flush();
        $this->em->clear();

        // Run lead segment update command
        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        Assert::assertStringContainsString('3 total contact(s) to be added', $output->getDisplay());

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);

        // All 3 leads should be in the segment (they all belong to companies that are in company segments)
        Assert::assertSame(3, $leadListRepository->getLeadCount([$leadSegmentTwo->getId()]));
    }

    public function testUpdateLeadSegmentsUsingExcludeACompanySegment(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');

        $leadOne   = $this->createLead('John Globo Doe', emailId: 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', emailId: 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', emailId: 'leadthree@mautic.com');
        $leadFour  = $this->createLead('Braw Doe', emailId: 'leadfour@mautic.com');

        $this->createCompanyLead($companyGlobo, $leadOne);
        $this->createCompanyLead($companyGlobo, $leadTwo);
        $this->createCompanyLead($companySbt, $leadThree);
        $this->createCompanyLead($companySbt, $leadFour);

        $totalCompanyLeadsBefore = $this->em->getRepository(CompanyLead::class)->findAll();
        Assert::assertCount(4, $totalCompanyLeadsBefore);

        $companySegmentOne = $this->createCompanySegment('Test Company Segment 1', 'test_comp_segment');
        $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);

        $resultSegmentCompaniesBefore = $this->em->getRepository(SegmentCompany::class)->findAll();
        Assert::assertCount(1, $resultSegmentCompaniesBefore);

        $filtersToLeadSegment = [
            [
                'glue'       => 'and',
                'operator'   => '!in',
                'properties' => [
                    'filter' => [$companySegmentOne->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];

        $this->createSegment('test_segment', $filtersToLeadSegment);

        $leadListModel = static::getContainer()->get('mautic.lead.model.list');
        assert($leadListModel instanceof \Mautic\LeadBundle\Model\ListModel);
        $leadListTotalBefore = $leadListModel->getListLeadRepository()->findAll();
        Assert::assertCount(0, $leadListTotalBefore);

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        Assert::assertStringContainsString('2 total contact(s) to be added', $output->getDisplay());

        $leadListTotalAfter = $leadListModel->getListLeadRepository()->findAll();
        Assert::assertCount(2, $leadListTotalAfter);
    }

    public function testUpdateCompanySegmentWithCompanySegmentMembershipFilter(): void
    {
        $companyGlobo  = $this->createCompany('Globo', 'contact@globo.com');
        $companySbt    = $this->createCompany('SBT', 'contact@sbt.com');
        $companyRecord = $this->createCompany('Record', 'contact@record.com');

        $leadOne   = $this->createLead('John Globo Doe', emailId: 'leadone@mautic.com');
        $leadTwo   = $this->createLead('Brian Doe', emailId: 'leadtwo@mautic.com');
        $leadThree = $this->createLead('Mat Doe', emailId: 'leadthree@mautic.com');

        $leadOne->setCompany($companySbt);
        $leadOne->setPrimaryCompany($companyGlobo);

        $leadTwo->setPrimaryCompany($companyRecord);

        $leadThree->setPrimaryCompany($companyRecord);
        $leadThree->setCompany($companyGlobo);

        $this->em->persist($leadOne);
        $this->em->persist($leadTwo);
        $this->em->persist($leadThree);
        $this->em->flush();

        $companySegmentOne = $this->createCompanySegment('Test Segment 1', 'test_segment');
        $this->addCompanyToCompanySegment($companyGlobo, $companySegmentOne);
        $filters = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$companySegmentOne->getId()],
                ],
                'field'  => 'company_segments',
                'type'   => 'company_segments',
                'object' => 'company_segments',
            ],
        ];
        $companySegmentTwo            = $this->createCompanySegment('Test Segment 2', 'test_segment2', true, $filters);
        $resultSegmentCompaniesBefore = $this->em->getRepository(SegmentCompany::class)->findAll();

        Assert::assertCount(1, $resultSegmentCompaniesBefore);

        $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        $resultSegmentCompaniesAfter = $this->em->getRepository(SegmentCompany::class)->findAll();
        Assert::assertCount(2, $resultSegmentCompaniesAfter);
        Assert::assertEquals($resultSegmentCompaniesAfter[0]->getCompany()->getId(), $resultSegmentCompaniesAfter[1]->getCompany()->getId());
        Assert::assertEquals($resultSegmentCompaniesAfter[1]->getCompanySegment()->getId(), $companySegmentTwo->getId());
    }

    public function testUpdateCompanySegmentsWithLeadListFilter(): void
    {
        $companyWithLeadWithoutSegment = $this->createCompany('noleadsegment', 'contact@globo.com');
        $companyWithLeadWithSegment1   = $this->createCompany('leadsegment1', 'contact@sbt.com');
        $companyWithLeadWithSegment2   = $this->createCompany('leadsegment2', 'contact@record.com');
        $companyWithoutLead            = $this->createCompany('companywithoutlead', 'companywithout@lead.com');

        $contactWithoutSegment = $this->createLead('Nosegment', emailId: 'leadone@mautic.com');
        $contactWithSegment1   = $this->createLead('Segment1', emailId: 'leadtwo@mautic.com');
        $contactWithSegment2   = $this->createLead('Segment2', emailId: 'leadthree@mautic.com');

        $leadSegment1 = $this->createSegment('segment_1', []);
        $leadSegment2 = $this->createSegment('segment_2', []);

        $this->addLeadToSegment($contactWithSegment1, $leadSegment1);
        $this->addLeadToSegment($contactWithSegment2, $leadSegment2);

        $this->createCompanyLead($companyWithLeadWithoutSegment, $contactWithoutSegment);
        $this->createCompanyLead($companyWithLeadWithSegment1, $contactWithSegment1);
        $this->createCompanyLead($companyWithLeadWithSegment2, $contactWithSegment2);

        $this->em->flush();

        $filterSegment1 = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$leadSegment1->getId()],
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterSegment2 = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'in',
                'properties' => [
                    'filter' => [$leadSegment2->getId()],
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterEmptySegment = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => 'empty',
                'properties' => [
                    'filter' => null,
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $filterNotEmptySegment = [
            'filters' => [
                'glue'       => 'and',
                'operator'   => '!empty',
                'properties' => [
                    'filter' => null,
                ],
                'field'  => 'contactsegmentmembership',
                'type'   => 'leadlist',
                'object' => 'any_companycontact',
            ],
        ];
        $companySegmentLeadList1        = $this->createCompanySegment('Lead List 1 Segment Filter', 'lead_list_1_segment_filter', true, $filterSegment1);
        $companySegmentLeadList2        = $this->createCompanySegment('Lead List 2 Segment Filter', 'lead_list_2_segment_filter', true, $filterSegment2);
        $companySegmentEmptyLeadList    = $this->createCompanySegment('Empty Lead Segments', 'empty_lead_segments', true, $filterEmptySegment);
        $companySegmentNotEmptyLeadList = $this->createCompanySegment('Not Empty Lead Segments', 'not_empty_lead_segments', true, $filterNotEmptySegment);

        $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, ['--bypass-locking' => true]);

        $companiesInSegment1 = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentLeadList1]);
        Assert::assertCount(1, $companiesInSegment1);
        Assert::assertEquals('leadsegment1', $companiesInSegment1[0]->getCompany()->getName());

        $companiesInSegment2 = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentLeadList2]);
        Assert::assertCount(1, $companiesInSegment2);
        Assert::assertEquals('leadsegment2', $companiesInSegment2[0]->getCompany()->getName());

        $companiesInEmptySegment = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentEmptyLeadList]);
        $companyNames = array_map(fn ($cs) => $cs->getCompany()->getName(), $companiesInEmptySegment);
        Assert::assertCount(2, $companiesInEmptySegment);
        Assert::assertContains('noleadsegment', $companyNames);
        Assert::assertContains('companywithoutlead', $companyNames);

        $companiesInNotEmptySegment = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegmentNotEmptyLeadList]);
        Assert::assertCount(2, $companiesInNotEmptySegment);
        $companyNames = array_map(fn ($cs) => $cs->getCompany()->getName(), $companiesInNotEmptySegment);
        Assert::assertContains('leadsegment1', $companyNames);
        Assert::assertContains('leadsegment2', $companyNames);
    }

    #[DataProvider('provideSegmentRebuildScenarios')]
    public function testSegmentRebuildScope(?string $useOption, bool $expectLeadRebuilt, bool $expectCompanyRebuilt): void
    {
        $contact = $this->createLead('Test', emailId: 'test@test.com');

        $leadSegment = $this->createSegment('lead-seg', [
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'test@test.com',
                'display'  => null,
                'operator' => 'eq',
            ],
        ]);

        $company = $this->createCompany('TestCo', 'co@test.com');
        $this->createCompanyLead($company, $contact);

        $companySegment = $this->createCompanySegment('Company Seg', 'company-seg', true, [
            [
                'glue'     => 'and',
                'operator' => '!empty',
                'field'    => 'companyemail',
                'type'     => 'email',
                'object'   => 'company',
            ],
        ]);

        $this->em->flush();

        $commandOptions = ['--bypass-locking' => true];

        if ('list-id' === $useOption) {
            $commandOptions['--list-id'] = $leadSegment->getId();
        } elseif ('companysegment-id' === $useOption) {
            $commandOptions['--companysegment-id'] = $companySegment->getId();
        }

        $output  = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, $commandOptions);
        $display = $output->getDisplay();

        /** @var LeadListRepository $leadListRepository */
        $leadListRepository = $this->em->getRepository(LeadList::class);
        $leadCount          = $leadListRepository->getLeadCount([$leadSegment->getId()]);
        $companiesInSegment = $this->em->getRepository(SegmentCompany::class)
            ->findBy(['companySegment' => $companySegment]);

        if ($expectLeadRebuilt) {
            Assert::assertSame(1, $leadCount);
            Assert::assertStringContainsString('1 total contact(s) to be added', $display);
        } else {
            Assert::assertSame(0, $leadCount);
        }

        if ($expectCompanyRebuilt) {
            Assert::assertCount(1, $companiesInSegment);
            Assert::assertStringContainsString('Rebuilding company segments', $display);
        } else {
            Assert::assertCount(0, $companiesInSegment);
            Assert::assertStringNotContainsString('Rebuilding company segments', $display);
        }
    }

    public static function provideSegmentRebuildScenarios(): \Generator
    {
        yield 'only lead segment with --list-id' => ['useOption' => 'list-id', 'expectLeadRebuilt' => true, 'expectCompanyRebuilt' => false];
        yield 'only company segment with --companysegment-id' => ['useOption' => 'companysegment-id', 'expectLeadRebuilt' => false, 'expectCompanyRebuilt' => true];
        yield 'both segment types without id specification' => ['useOption' => null, 'expectLeadRebuilt' => true, 'expectCompanyRebuilt' => true];
    }

    public function testExcludeCompanySegmentIdSkipsExcludedSegment(): void
    {
        $this->createCompany('Company1', 'c1@test.com');
        $this->createCompany('Company2', 'c2@test.com');

        $companyEmailFilter = [
            [
                'glue'     => 'and',
                'operator' => '!empty',
                'field'    => 'companyemail',
                'type'     => 'email',
                'object'   => 'company',
            ],
        ];

        $companySegment1 = $this->createCompanySegment('Segment 1', 'seg-1', true, $companyEmailFilter);
        $companySegment2 = $this->createCompanySegment('Segment 2', 'seg-2', true, $companyEmailFilter);

        $this->em->flush();

        $output = $this->testSymfonyCommand(UpdateSegmentsCommand::NAME, [
            '--exclude-companysegment-id' => [$companySegment1->getId()],
            '--bypass-locking'            => true,
        ]);

        $display = $output->getDisplay();

        // Segment 2 was rebuilt
        Assert::assertStringContainsString(
            sprintf('Rebuilding company segments for segment %d', $companySegment2->getId()),
            $display
        );

        // Segment 1 was excluded
        Assert::assertStringNotContainsString(
            sprintf('Rebuilding company segments for segment %d', $companySegment1->getId()),
            $display
        );
    }

    private function addLeadToSegment(Lead $lead, LeadList $segment): void
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $listLead->setManuallyAdded(true);
        $listLead->setManuallyRemoved(false);
        $this->em->persist($listLead);
        $this->em->flush();
    }
}
