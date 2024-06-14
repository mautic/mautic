<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Helper\IntHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected ListModel $listModel;

    private string $prefix;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listModel  = static::getContainer()->get('mautic.lead.model.list');
        $this->prefix     = static::getContainer()->getParameter('mautic.db_table_prefix');
        $this->translator = static::getContainer()->get('translator');
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    /**
     * @return iterable<array<string|int|null>>
     */
    public static function regexOperatorProvider(): iterable
    {
        yield [
            'regexp',
            '^{Test|Test string)', // invalid regex: the first parantheses should not be curly
            Response::HTTP_BAD_REQUEST,
            'error',
        ];

        yield [
            '!regexp',
            '^(Test|Test string))', // invalid regex: 2 ending parantheses
            Response::HTTP_BAD_REQUEST,
            'error',
        ];

        yield [
            'regexp',
            '^(Test|Test string)', // valid regex
            Response::HTTP_CREATED,
            null,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('regexOperatorProvider')]
    public function testRegexOperatorValidation(string $operator, string $regex, int $expectedResponseCode, ?string $expectedErrorMessage): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/segments/new',
            [
                'name'    => 'Regex test',
                'filters' => [
                    [
                        'glue'       => 'and',
                        'field'      => 'city',
                        'object'     => 'lead',
                        'type'       => 'text',
                        'operator'   => $operator,
                        'properties' => ['filter' => $regex],
                    ],
                ],
            ]
        );

        Assert::assertSame($expectedResponseCode, $this->client->getResponse()->getStatusCode());

        if ($expectedErrorMessage) {
            Assert::assertStringContainsString(
                $expectedErrorMessage,
                json_decode($this->client->getResponse()->getContent(), true)['errors'][0]['message'],
                $this->client->getResponse()->getContent()
            );
        }
    }

    public function testSingleSegmentWorkflow(): void
    {
        $payload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
            'filters'     => [
                // Legacy structure.
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'city',
                    'type'     => 'text',
                    'filter'   => 'Prague',
                    'display'  => null,
                    'operator' => '=',
                ],
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'owner_id',
                    'type'     => 'lookup_id',
                    'operator' => '=',
                    'display'  => 'John Doe',
                    'filter'   => '4',
                ],
                // Current structure.
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'properties' => ['filter' => 'Prague'],
                    'operator'   => '=',
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'display'    => 'outdated name',
                    'filter'     => 'outdated_id',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                ],
                [
                    'glue'     => 'and',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'email',
                    'operator' => '!empty',
                    'display'  => '',
                ],
            ],
        ];

        // Create:
        $this->client->request('POST', '/api/segments/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        $segmentId = $response['list']['id'];

        $this->assertSame(201, $clientResponse->getStatusCode());
        $this->assertGreaterThan(0, $segmentId);
        $this->assertEquals($payload['name'], $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);
        $this->assertEquals([
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'city',
                'type'       => 'text',
                'properties' => ['filter' => 'Prague'],
                'operator'   => '=',
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'owner_id',
                'type'       => 'lookup_id',
                'operator'   => '=',
                'properties' => [
                    'display' => 'John Doe',
                    'filter'  => '4',
                ],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'city',
                'type'       => 'text',
                'properties' => ['filter' => 'Prague'],
                'operator'   => '=',
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'owner_id',
                'type'       => 'lookup_id',
                'operator'   => '=',
                'properties' => [
                    'display' => 'John Doe',
                    'filter'  => '4',
                ],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'email',
                'type'       => 'email',
                'operator'   => '!empty',
                'properties' => [
                    'filter'  => null,
                ],
            ],
        ],
            $response['list']['filters']
        );

        // Edit:
        $this->client->request('PATCH', "/api/segments/{$segmentId}/edit", ['name' => 'API segment renamed']);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the edited one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get:
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the fetched one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Delete:
        $this->client->request('DELETE', "/api/segments/{$segmentId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertNull($response['list']['id']);
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode());
        $this->assertSame(404, $response['errors'][0]['code']);
    }

    public function testBatchSegmentWorkflow(): void
    {
        $payload = [
            [
                'name'        => 'API batch segment 1',
                'description' => 'Segment created via API test',
                'filters'     => [
                    // Legacy structure.
                    [
                        'object'   => 'lead',
                        'glue'     => 'and',
                        'field'    => 'city',
                        'type'     => 'text',
                        'filter'   => 'Prague',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    // Current structure.
                    [
                        'object'     => 'lead',
                        'glue'       => 'and',
                        'field'      => 'city',
                        'type'       => 'text',
                        'properties' => ['filter' => 'Prague'],
                        'operator'   => '=',
                    ],
                ],
            ],
            [
                'name'        => 'API batch segment 2',
                'description' => 'Segment created via API test',
            ],
        ];

        $this->client->request('POST', '/api/segments/batch/new', $payload);
        $clientResponse  = $this->client->getResponse();
        $response1       = json_decode($clientResponse->getContent(), true);

        if (!empty($response1['errors'][0])) {
            $this->fail($response1['errors'][0]['code'].': '.$response1['errors'][0]['message']);
        }

        foreach ($response1['statusCodes'] as $statusCode) {
            $this->assertSame(201, $statusCode);
        }

        foreach ($response1['lists'] as $key => $segment) {
            $this->assertGreaterThan(0, $segment['id']);
            $this->assertTrue($segment['isPublished']);
            $this->assertTrue($segment['isGlobal']);
            $this->assertFalse($segment['isPreferenceCenter']);
            $this->assertSame($payload[$key]['name'], $segment['name']);
            $this->assertSame($payload[$key]['description'], $segment['description']);
            $this->assertIsArray($segment['filters']);

            // Make a change for the edit request:
            $response1['lists'][$key]['isPublished'] = false;
        }

        // Lets try to create the same segment to see that the values are not re-setted
        $this->client->request('PATCH', '/api/segments/batch/edit', $response1['lists']);
        $clientResponse  = $this->client->getResponse();
        $response2       = json_decode($clientResponse->getContent(), true);

        if (!empty($response2['errors'][0])) {
            $this->fail($response2['errors'][0]['code'].': '.$response2['errors'][0]['message']);
        }

        foreach ($response2['statusCodes'] as $statusCode) {
            $this->assertSame(200, $statusCode);
        }

        foreach ($response2['lists'] as $key => $segment) {
            $this->assertGreaterThan(0, $segment['id']);
            $this->assertFalse($segment['isPublished']);
            $this->assertTrue($segment['isGlobal']);
            $this->assertFalse($segment['isPreferenceCenter']);
            $this->assertSame($payload[$key]['name'], $segment['name']);
            $this->assertSame($payload[$key]['description'], $segment['description']);
        }

        $this->assertSame(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => ['filter' => 'Prague'],
                    'filter'     => 'Prague',
                    'display'    => null,
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => ['filter' => 'Prague'],
                    'filter'     => 'Prague',
                    'display'    => null,
                ],
            ],
            $response2['lists'][0]['filters']
        );

        $this->assertSame([], $response2['lists'][1]['filters']);
    }

    public function testWeGet422ResponseCodeIfSegmentIsBeingUsedInSomeCampaignAndWeUnpublishIt(): void
    {
        $segmentName = 'Segment1';
        $segment     = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(mb_strtolower($segmentName));
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $campaign     = new Campaign();
        $campaignName = 'Campaign1';
        $campaign->setName($campaignName);

        $this->em->persist($campaign);
        $this->em->flush();

        // insert unpublished record
        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id'   => $campaign->getId(),
            'leadlist_id'   => $segment->getId(),
        ]);

        $this->client->request('PATCH', "/api/segments/{$segment->getId()}/edit", ['isPublished' => 0]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $clientResponse->getStatusCode());
        Assert::assertArrayHasKey('errors', $response);
        $errorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns',
            [
                '%count%'         => '1',
                '%campaignNames%' => '"'.$campaignName.'"',
            ],
            'validators'
        );
        Assert::assertStringContainsString($errorMessage, $response['errors'][0]['message']);
    }

    public function testWeGet200ResponseCodeIfSegmentIsNotUsedInCampaignsAndWeUnpublishIt(): void
    {
        $segmentName = 'Segment1';
        $segment     = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(mb_strtolower($segmentName));
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $campaign = new Campaign();
        $campaign->setName('campaign1');

        $this->em->persist($campaign);
        $this->em->flush();

        $this->client->request('PATCH', "/api/segments/{$segment->getId()}/edit", ['isPublished' => 0]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertArrayNotHasKey('errors', $response);
    }

    public function testUnpublishUsedSingleSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();
        $expectedErrorMessage = sprintf('leadlist: This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

        $this->client->request('PATCH', "/api/segments/{$list1->getId()}/edit", ['name' => 'API segment renamed', 'isPublished' => false]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $clientResponse->getStatusCode());
        $this->assertSame($response['errors'][0]['message'], $expectedErrorMessage);
    }

    public function testUnpublishUsedBatchSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();
        $expectedErrorMessage = sprintf('leadlist: This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

        $segments = [
            ['id' => $list1->getId(), 'isPublished' => false],
            ['id' => $list2->getId(), 'isPublished' => false],
        ];

        $this->client->request('PATCH', '/api/segments/batch/edit', $segments);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response['statusCodes'][0]);
        $this->assertSame($response['errors'][0]['message'], $expectedErrorMessage);

        $this->assertSame(Response::HTTP_OK, $response['statusCodes'][1]);
    }

    public function testSegmentWithCategory(): void
    {
        $categoryPayload = [
            'title'  => 'API Cat',
            'alias'  => 'kitty',
            'bundle' => 'segment',
        ];
        $this->client->request('POST', '/api/categories/new', $categoryPayload);
        $clientResponse     = $this->client->getResponse();
        $response           = json_decode($clientResponse->getContent(), true);
        $categoryId         = $response['category']['id'];

        $segmentPayload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
            'category'    => $categoryId,
        ];

        // Create:
        $this->client->request('POST', '/api/segments/new', $segmentPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        $segmentId = $response['list']['id'];

        // Get segment with category by id:
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue($clientResponse->isOk());
        $this->assertEquals($segmentPayload['category'], $response['list']['category']['id']);

        // Search segments by category:
        $this->client->request('GET', '/api/segments?search=category:kitty');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue($clientResponse->isOk());
        $this->assertCount(1, $response['lists']);
    }

    public function testAbsoluteDateFilter(): void
    {
        $filters = [
            [
                'glue'        => 'and',
                'field'       => 'date_added',
                'object'      => 'lead',
                'type'        => 'date',
                'operator'    => 'like',
                'properties'  => [
                    'filter' => (new \DateTime())->format('Y-m-d'),
                ],
            ],
            [
                'glue'        => 'and',
                'field'       => 'date_identified',
                'object'      => 'lead',
                'type'        => 'datetime',
                'operator'    => 'gt',
                'properties'  => [
                    'filter' => [
                        'dateTypeMode'             => 'absolute',
                        'absoluteDate'             => '-1 day',
                        'relativeDateInterval'     => '1',
                        'relativeDateIntervalUnit' => 'day',
                    ],
                ],
            ],
        ];

        $segment = $this->createSegment($filters);

        $contactA = new Lead();
        $contactA->setDateIdentified(new \DateTime('-2 day')); // 2 days before the date_identified - won't get to the segment
        $contactA->setDateAdded(new \DateTime('-2 day'));

        $contactB = new Lead();
        $contactB->setDateIdentified(new \DateTime('+1 hour'));
        $contactB->setDateAdded(new \DateTime());

        $contactC = new Lead();
        $contactC->setDateIdentified(new \DateTime('+1 hour'));
        $contactC->setDateAdded(new \DateTime());

        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->persist($contactC);
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segment->getId()]);

        Assert::assertSame(0, $commandTester->getStatusCode());

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);

        Assert::assertCount(2, $members);

        $expectedMembers = [$contactB->getId(), $contactC->getId()];
        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        Assert::assertSame($expectedMembers, $actualMembers);
    }

    /**
     * @dataProvider errorProvider
     */
    public function testBetweenFilterErrorsForNumberType(string $value, string $errorMessage): void
    {
        $filters = [[
            'glue'       => 'and',
            'field'      => 'points',
            'object'     => 'lead',
            'type'       => 'number',
            'operator'   => 'between',
            'properties' => [
                'filter' => [
                    'number_from' => 0,
                    'number_to'   => $value,
                ],
            ],
        ]];

        $this->client->request('POST', '/api/segments/new', ['name' => 'Points range filter', 'filters' => $filters]);

        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $clientResponse->getStatusCode());
        Assert::assertStringContainsString($errorMessage, $clientResponse->getContent(), 'The error message was not found in the response');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errorProvider(): array
    {
        return [
            'invalid_value'         => ['abc', 'This value should be a valid number.'],
            'empty_value'           => ['', 'A value is required.'],
        ];
    }

    /**
     * @param int|float $lowerLimit
     * @param int|float $upperLimit
     *
     * @dataProvider limitProvider
     */
    public function testBetweenFilterForNumberType(string $operator, $lowerLimit, $upperLimit, int $memberCount): void
    {
        $filters = [[
            'glue'        => 'and',
            'field'       => 'points',
            'object'      => 'lead',
            'type'        => 'number',
            'operator'    => $operator,
            'properties'  => [
                'filter' => [
                    'number_from' => $lowerLimit,
                    'number_to'   => $upperLimit,
                ],
            ],
        ]];

        $this->client->request('POST', '/api/segments/new', ['name' => 'Points range filter', 'filters' => $filters]);

        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode());
        $segmentId = json_decode($clientResponse->getContent(), true)['list']['id'];

        $leadWithLessThanLowerLimitPoints = new Lead();
        $leadWithLessThanLowerLimitPoints->setPoints($lowerLimit - 5);

        $leadWithMoreThanUpperLimitPoints = new Lead();
        $leadWithMoreThanUpperLimitPoints->setPoints($upperLimit + 5);

        $leadWithLowerLimitPoints = new Lead();
        $leadWithLowerLimitPoints->setPoints($lowerLimit);

        $leadWithUpperLimitPoints = new Lead();
        $leadWithUpperLimitPoints->setPoints($upperLimit);

        $leadWithMeanOfLimitPoints = new Lead();
        $leadWithMeanOfLimitPoints->setPoints(($lowerLimit + $upperLimit) / 2);

        $this->em->persist($leadWithLessThanLowerLimitPoints);
        $this->em->persist($leadWithMoreThanUpperLimitPoints);
        $this->em->persist($leadWithLowerLimitPoints);
        $this->em->persist($leadWithUpperLimitPoints);
        $this->em->persist($leadWithMeanOfLimitPoints);
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segmentId]);

        Assert::assertSame(0, $commandTester->getStatusCode(), 'Update lead lists command was not successful');

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segmentId]);

        Assert::assertCount($memberCount, $members, 'The segment does not have the expected number of members');

        $leadsWithinRange = [$leadWithMeanOfLimitPoints->getId(), $leadWithUpperLimitPoints->getId(), $leadWithLowerLimitPoints->getId()];
        $leadsOutOfRange  = [$leadWithLessThanLowerLimitPoints->getId(), $leadWithMoreThanUpperLimitPoints->getId()];

        if (0 === $memberCount) {
            $expectedMembers = [];
        } elseif (5 === $memberCount) {
            $expectedMembers = array_merge($leadsWithinRange, $leadsOutOfRange);
        } else {
            $expectedMembers = OperatorOptions::BETWEEN === $operator ? $leadsWithinRange : $leadsOutOfRange;
        }
        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        Assert::assertSame($expectedMembers, $actualMembers, 'The expected amd the actual members in the segment do not match');
    }

    /**
     * @return array<string, array<int, float|int|string>>
     */
    public function limitProvider(): array
    {
        return [
            'both_positive_between'                                    => [OperatorOptions::BETWEEN, 5, 15, 3],
            'both_negative_between'                                    => [OperatorOptions::BETWEEN, -15, -5, 3],
            'negative_positive_between'                                => [OperatorOptions::BETWEEN, -5, 5, 3],
            'both_positive_not_between'                                => [OperatorOptions::NOT_BETWEEN, 5, 15, 2],
            'both_negative_not_between'                                => [OperatorOptions::NOT_BETWEEN, -15, -5, 2],
            'negative_positive_not_between'                            => [OperatorOptions::NOT_BETWEEN, -5, 5, 2],
            'first_number_greater_than_second_between'                 => [OperatorOptions::BETWEEN, 15, 5, 0],
            'first_number_greater_than_second_not_between'             => [OperatorOptions::NOT_BETWEEN, 15, 5, 5],
            'float_numbers_between'                                    => [OperatorOptions::BETWEEN, 5.0, 15.5, 3],
            'float_numbers_not_between'                                => [OperatorOptions::NOT_BETWEEN, 5.0, 15.5, 2],
            'int_max_between'                                          => [OperatorOptions::BETWEEN, IntHelper::MIN_INTEGER_VALUE + 5, IntHelper::MAX_INTEGER_VALUE - 5, 3],
            'int_max_not_between'                                      => [OperatorOptions::NOT_BETWEEN, IntHelper::MIN_INTEGER_VALUE + 5, IntHelper::MAX_INTEGER_VALUE - 5, 2],
        ];
    }

    /**
     * @dataProvider operatorProvider
     */
    public function testInTheLastAndInTheNextFilter(string $operator, int $expected): void
    {
        $filters = [
            [
                'glue'        => 'and',
                'field'       => 'date_modified',
                'object'      => 'lead',
                'type'        => 'datetime',
                'operator'    => $operator,
                'properties'  => [
                    'filter' => [
                        'interval' => '1',
                        'unit'     => 'day',
                    ],
                ],
            ],
        ];

        $segment = $this->createSegment($filters);

        $contactA = new Lead();
        $contactA->setDateModified(new \DateTime('-1 hour'));

        $contactB = new Lead();
        $contactB->setDateModified((new \DateTime())->modify('+1 day'));

        $contactC = new Lead();
        $contactC->setDateModified((new \DateTime())->modify('-1 day'));

        $contactD = new Lead();
        $contactD->setDateModified((new \DateTime())->modify('-2 day'));

        $contactE = new Lead();
        $contactE->setDateModified((new \DateTime())->modify('+2 day'));

        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->persist($contactC);
        $this->em->persist($contactD);
        $this->em->persist($contactE);
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segment->getId()]);

        Assert::assertSame(0, $commandTester->getStatusCode());

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);

        Assert::assertCount($expected, $members);

        $expectedMembersForOperator = [
            OperatorOptions::IN_LAST => [$contactA->getId(), $contactC->getId()],
            OperatorOptions::IN_NEXT => [$contactA->getId(), $contactB->getId()],
        ];

        $expectedMembers = $expectedMembersForOperator[$operator];

        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        Assert::assertSame($expectedMembers, $actualMembers);
    }

    /**
     * @dataProvider operatorProvider
     */
    public function testInLastAndInNextFilterForCompanyCustomField(string $operator, int $expectedCount): void
    {
        $company = $this->createCompanyWithDateCustomField('CompanyABC', $operator);

        $filters = [
            [
                'glue'        => 'and',
                'field'       => 'company_created_at',
                'object'      => 'company',
                'type'        => 'datetime',
                'operator'    => $operator,
                'properties'  => [
                    'filter' => [
                        'interval' => '1',
                        'unit'     => 'day',
                    ],
                ],
            ],
        ];
        $segment = $this->createSegment($filters);

        $contactA = new Lead();
        $contactA->setDateModified(new \DateTime('-1 hour'));

        $contactB = new Lead();
        $contactB->setDateModified((new \DateTime())->modify('+1 day'));

        $contactC = new Lead();
        $contactC->setDateModified((new \DateTime())->modify('-1 day'));

        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->persist($contactC);

        $this->createCompanyLeadRelation($company, $contactB);
        $this->createCompanyLeadRelation($company, $contactC);

        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segment->getId()]);

        Assert::assertSame(0, $commandTester->getStatusCode());

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);

        Assert::assertCount($expectedCount, $members);

        $expectedMembers = [$contactB->getId(), $contactC->getId()];

        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        Assert::assertSame($expectedMembers, $actualMembers);
    }

    public function operatorProvider(): \Generator
    {
        yield [OperatorOptions::IN_LAST, 2];
        yield [OperatorOptions::IN_NEXT, 2];
    }

    private function saveSegment(string $name, string $alias, array $filters = [], LeadList $segment = null): LeadList
    {
        $segment ??= new LeadList();
        $segment->setName($name)->setPublicName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }
}
