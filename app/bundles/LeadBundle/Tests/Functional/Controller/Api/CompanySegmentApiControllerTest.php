<?php

namespace Mautic\LeadBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\CompanySegment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanySegmentApiControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->useCleanupRollback = false;
        parent::setUp();
    }

    public function testGetCompanySegments(): void
    {
        $this->createCompanySegment('Segment test', 'segment-test', true);
        $this->createCompanySegment('Segment test 2', 'segment-test-2', true);
        $this->client->request(Request::METHOD_GET, '/api/companysegments');
        $response = $this->client->getResponse();
        self::assertNotFalse($response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegments']);
        self::assertCount(2, $data['companysegments']);
    }

    public function testGetCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Segment test', 'segment-test', true);
        $this->client->request(Request::METHOD_GET, '/api/companysegments/'.$companySegment->getId());
        $response = $this->client->getResponse();
        self::assertNotFalse($response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegment']);
        self::assertArrayHasKey('name', $data['companysegment']);
        self::assertSame('Segment test', $data['companysegment']['name']);
    }

    public function testAddCompanySegment(): void
    {
        $data = [
            'name'        => 'Segment test',
            'alias'       => 'segment-test-a',
            'isPublished' => '1',
        ];
        $this->client->request(Request::METHOD_POST, '/api/companysegments/new', $data);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegment']);
        self::assertSame('Segment test', $data['companysegment']['name']);
        $companySegment = $this->em->getRepository(CompanySegment::class)->find($data['companysegment']['id']);
        self::assertNotNull($companySegment);
        assert($companySegment instanceof CompanySegment);
        self::assertSame('Segment test', $companySegment->getName());
    }

    public function testEditCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Segment test', 'segment-test', true);
        $data           = [
            'name'        => 'Segment test edited',
            'alias'       => 'segment-test-a',
            'isPublished' => '1',
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/companysegments/'.$companySegment->getId().'/edit', $data);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegment']);
        self::assertArrayHasKey('name', $data['companysegment']);
        self::assertIsString($data['companysegment']['name']);
        self::assertSame('Segment test edited', $data['companysegment']['name']);
        $companySegment = $this->em->getRepository(CompanySegment::class)->find($data['companysegment']['id']);
        self::assertNotNull($companySegment);
        assert($companySegment instanceof CompanySegment);
        self::assertSame('Segment test edited', $companySegment->getName());
    }

    public function testDeleteCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Segment test', 'segment-test', true);
        $tempId         = $companySegment->getId();
        $this->client->request(Request::METHOD_DELETE, '/api/companysegments/'.$companySegment->getId().'/delete');
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $companySegment = $this->em->getRepository(CompanySegment::class)->find($tempId);
        self::assertNull($companySegment);
    }

    public function testBatchAddCompanySegments(): void
    {
        $data = [
            [
                'name'        => 'Segment test edited',
                'alias'       => 'segment-test-a',
                'isPublished' => '1',
            ],
            [
                'name'        => 'Segment test 2 edited',
                'alias'       => 'segment-test-2-a',
                'isPublished' => '1',
            ],
        ];
        $this->client->request(Request::METHOD_POST, '/api/companysegments/batch/new', $data);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegments']);
        self::assertCount(2, $data['companysegments']);
        self::assertIsArray($data['statusCodes']);
        self::assertArrayHasKey(0, $data['statusCodes']);
        self::assertArrayHasKey(1, $data['statusCodes']);
        self::assertSame(Response::HTTP_CREATED, $data['statusCodes'][0]);
        self::assertSame(Response::HTTP_CREATED, $data['statusCodes'][1]);
    }

    public function testAddBatchCompanySegmentOneSuccessAndOneFail(): void
    {
        $data = [
            [
                'name'        => 'Segment test edited',
                'alias'       => 'segment-test-a',
                'isPublished' => '1',
            ],
            [
                'alias'       => 'segment-test-2-a',
                'isPublished' => '1',
            ],
        ];
        $this->client->request(Request::METHOD_POST, '/api/companysegments/batch/new', $data);
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegments']);
        self::assertCount(1, $data['companysegments']);
        self::assertIsArray($data['statusCodes']);
        self::assertArrayHasKey(0, $data['statusCodes']);
        self::assertArrayHasKey(1, $data['statusCodes']);
        self::assertSame(Response::HTTP_CREATED, $data['statusCodes'][0]);
        self::assertSame(Response::HTTP_BAD_REQUEST, $data['statusCodes'][1]);
        self::assertIsArray($data['errors']);
        self::assertCount(1, $data['errors']);
    }

    public function testBatchEditCompanySegments(): void
    {
        $companySegment1 = $this->createCompanySegment('Segment test', 'segment-test', true);
        $companySegment2 = $this->createCompanySegment('Segment test 2', 'segment-test-2', true);
        $data            = [
            [
                'id'          => $companySegment1->getId(),
                'name'        => 'Segment test edited',
                'alias'       => 'segment-test-a',
                'isPublished' => '1',
            ],
            [
                'id'          => $companySegment2->getId(),
                'name'        => 'Segment test 2 edited',
                'alias'       => 'segment-test-2-a',
                'isPublished' => '0',
            ],
        ];
        $this->client->request(Request::METHOD_PATCH, '/api/companysegments/batch/edit', $data);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegments']);
        self::assertCount(2, $data['companysegments']);
        self::assertIsArray($data['statusCodes']);
        self::assertArrayHasKey(0, $data['statusCodes']);
        self::assertArrayHasKey(1, $data['statusCodes']);
        self::assertSame(Response::HTTP_OK, $data['statusCodes'][0]);
        self::assertSame(Response::HTTP_OK, $data['statusCodes'][1]);
        self::assertSame('Segment test edited', $data['companysegments'][0]['name']);
        self::assertSame('Segment test 2 edited', $data['companysegments'][1]['name']);
    }
}
