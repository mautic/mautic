<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Validator\Constraints;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;

final class CircularDependencyValidatorTest extends MauticMysqlTestCase
{
    /**
     * Verify a constraint message.
     *
     * @dataProvider validateDataProvider
     */
    public function testValidateOnInvalid(string $expectedMessage, int $expectedCode, string $segmentKeyToUpdate, callable $newSegmentFiltersBuilder): void
    {
        $segment3 = new LeadList();
        $segment3->setName('Segment 3');
        $segment3->setPublicName('Segment 3');
        $segment3->setAlias('segment-3');
        $segment3->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'first_name',
                'object'     => 'lead',
                'type'       => 'text',
                'properties' => ['filter' => 'John'],
                'display'    => null,
                'operator'   => '=',
            ],
        ]);

        $this->em->persist($segment3);
        $this->em->flush();

        $segment2 = new LeadList();
        $segment2->setName('Segment 2');
        $segment2->setPublicName('Segment 2');
        $segment2->setAlias('segment-2');

        $this->em->persist($segment2);
        $this->em->flush();

        $segment1 = new LeadList();
        $segment1->setName('Segment 1');
        $segment1->setPublicName('Segment 1');
        $segment1->setAlias('segment-1');
        $segment1->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'leadlist_static',
                'object'   => 'lead',
                'type'     => 'leadlist',
                'filter'   => [$segment2->getId()], // Keeping filter in the root to test also for BC segments.
                'display'  => null,
                'operator' => 'in',
            ],
        ]);

        $this->em->persist($segment1);
        $this->em->flush();

        $segment2->setFilters([
            [
                'glue'       => 'and',
                'field'      => 'leadlist',
                'object'     => 'lead',
                'type'       => 'leadlist',
                'properties' => ['filter' => [$segment1->getId()]],
                'display'    => null,
                'operator'   => 'in',
            ],
        ]);
        $this->em->persist($segment2);
        $this->em->flush();
        $this->em->clear();

        $existingSegments = [
            $segment1->getAlias() => $segment1,
            $segment2->getAlias() => $segment2,
            $segment3->getAlias() => $segment3,
        ];

        $segmentIdToUpdate = $existingSegments[$segmentKeyToUpdate]->getId();
        $this->client->request(
            'PATCH',
            "/api/segments/{$segmentIdToUpdate}/edit",
            [
                'name'        => "API changed ({$segmentKeyToUpdate})",
                'description' => 'Segment created via API test',
                'filters'     => $newSegmentFiltersBuilder($existingSegments),
            ]
        );
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame($expectedCode, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals($expectedMessage, $response['errors'][0]['message'] ?? '');
    }

    /**
     * @return mixed[]
     */
    public function validateDataProvider(): array
    {
        return [
            'Segment 1 is dependent on Segment 2 which is dependent on segment 1 - circular' => [
                'filters: Circular dependency detected in the segment membership filter. API changed (segment-2) > Segment 1 > API changed (segment-2). This operation would create an infinite loop. Please double check what you are intending to do.',
                422,
                'segment-2',
                fn (array $existingSegments) => [
                    [
                        'glue'     => 'and',
                        'field'    => 'leadlist',
                        'object'   => 'lead',
                        'type'     => 'leadlist',
                        'filter'   => [$existingSegments['segment-1']->getId()], // Keeping filter in the root to test also for BC segments.
                        'operator' => 'in',
                    ],
                ],
            ],
            'Segment 2 is dependent on Segment 1 which is dependent on segment 2 - circular' => [
                'filters: Circular dependency detected in the segment membership filter. API changed (segment-1) > Segment 2 > API changed (segment-1). This operation would create an infinite loop. Please double check what you are intending to do.',
                422,
                'segment-1',
                fn (array $existingSegments) => [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist_static',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [$existingSegments['segment-3']->getId(), $existingSegments['segment-2']->getId()]],
                        'operator'   => 'in',
                    ],
                ],
            ],
            // Test when there are no validation errors
            'The segment in the filter (3) is NOT dependent on any' => [
                '',
                200,
                'segment-1',
                fn (array $existingSegments) => [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [$existingSegments['segment-3']->getId()]],
                        'operator'   => 'in',
                    ],
                ],
            ],
            'Test multiple lead list filters. Fails because 2 is dependent on 1' => [
                'filters: Circular dependency detected in the segment membership filter. API changed (segment-2) > Segment 1 > API changed (segment-2). This operation would create an infinite loop. Please double check what you are intending to do.',
                422,
                'segment-2',
                fn (array $existingSegments) => [
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [$existingSegments['segment-1']->getId()]],
                        'operator'   => 'in',
                    ],
                    [
                        'glue'       => 'and',
                        'field'      => 'leadlist',
                        'object'     => 'lead',
                        'type'       => 'leadlist',
                        'properties' => ['filter' => [$existingSegments['segment-3']->getId()]],
                        'operator'   => 'in',
                    ],
                ],
            ],
            // @TODO: MUST ADD TEST CASES ONCE WE FIX DEEP CIRCULAR (1 depends on 2 which depends on 3 which depends on 1) TO AN ARBITRARY DEPTH
        ];
    }
}
