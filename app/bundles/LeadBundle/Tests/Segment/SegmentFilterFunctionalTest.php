<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentService;

class SegmentFilterFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var Lead[]
     */
    private $leads = [];

    /**
     * Test creates: contacts, segment
     * Test rebuilds segment
     * Test check that the right contacts are in the segment.
     */
    public function testSegments(): void
    {
        foreach ($this->getSegmentsProvider() as $scenario) {
            $this->runTestSegments($scenario['contacts'], $scenario['segment']);
        }
    }

    /**
     * @param mixed[] $contacts
     * @param mixed[] $segment
     */
    private function runTestSegments(array $contacts, array $segment): void
    {
        $countInSegment = $this->createLeads($contacts);
        $leadList       = $this->createSegment($segment);
        $this->buildSegment($leadList, $countInSegment);
        $this->cleanAfterTest($leadList);
    }

    /**
     * @param mixed[] $contacts
     */
    private function createLeads(array $contacts): int
    {
        $countInSegment = 0;
        foreach ($contacts as $contact) {
            $lead = $this->createLead($contact);
            $this->em->persist($lead);
            $this->leads[] = $lead;
            if ($contact['in_segment']) {
                ++$countInSegment;
            }
        }
        $this->em->flush();

        return $countInSegment;
    }

    /**
     * @param mixed[] $values
     */
    private function createLead(array $values): Lead
    {
        $lead = new Lead();
        foreach ($values as $field => $value) {
            if ('in_segment' === $field) {
                continue;
            }
            call_user_func_array([$lead, 'set'.$field], [$value]);
        }

        return $lead;
    }

    /**
     * @param mixed[] $segmentFilters
     */
    private function createSegment(array $segmentFilters): LeadList
    {
        $filters = [];
        foreach ($segmentFilters as $segmentFilter) {
            $filters[] = [
                'object'     => 'lead',
                'glue'       => $segmentFilter['glue'],
                'field'      => $segmentFilter['field'],
                'type'       => $segmentFilter['type'],
                'properties' => ['filter' => $segmentFilter['value']],
                'operator'   => $segmentFilter['operator'],
            ];
        }

        $payload = [
            'name'        => 'API segment',
            'alias'       => 'api_segment_test',
            'description' => 'Segment created via API',
            'filters'     => $filters,
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

        return $this->em->getRepository(LeadList::class)->find($segmentId);
    }

    private function buildSegment(LeadList $segment, int $expectedCountInSegment): void
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = self::$container->get('mautic.lead.model.lead_segment_service');

        $this->testSymfonyCommand('mautic:segments:update', [
            '-i'    => $segment->getId(),
            '--env' => 'test',
        ]);

        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segment);
        $this->assertEquals(
            $expectedCountInSegment,
            $segmentContacts[$segment->getId()]['count']
        );
    }

    private function cleanAfterTest(LeadList $segment): void
    {
        $this->em->remove($segment);
        foreach ($this->leads as $lead) {
            $deleteLead = $this->em->getRepository(Lead::class)->find($lead->getId());
            $this->em->remove($deleteLead);
        }
        $this->em->flush();
        $this->leads = [];
    }

    /**
     * @see self::testSegments
     *
     * @return \Generator<int,mixed>
     */
    private function getSegmentsProvider(): \Generator
    {
        yield [
            'contacts' => [
                ['email' => 'lukas@mautic.com', 'in_segment' => true, 'city' => 'Prague'],
                ['email' => 'lukas2@mautic.com', 'in_segment' => true, 'city' => 'Prague 11'],
                ['email' => 'lukas3@mautic.com', 'in_segment' => false, 'city' => 'Praha'],
            ],
            'segment' => [
                ['field' => 'city', 'operator' => 'startsWith', 'value' => 'Prague', 'glue' => 'and', 'type' => 'text'],
            ],
        ];
        yield [
            'contacts' => [
                ['email' => 'lukas@mautic.com', 'in_segment' => true, 'points' => 20],
                ['email' => 'lukas2@mautic.com', 'in_segment' => false, 'points' => 10],
                ['email' => 'lukas3@mautic.com', 'in_segment' => true, 'points' => 25],
            ],
            'segment' => [
                ['field' => 'points', 'operator' => 'gte', 'value' => 20, 'glue' => 'and', 'type' => 'text'],
            ],
        ];
    }
}
