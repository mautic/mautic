<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanySegmentApiControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    use UserEntityTrait;

    private const API_ENDPOINT = '/api/companysegments';

    protected function setUp(): void
    {
        $this->useCleanupRollback = false;
        parent::setUp();
    }

    public function testGetCompanySegments(): void
    {
        $this->createCompanySegment('Segment test', 'segment-test', true);
        $this->createCompanySegment('Segment test 2', 'segment-test-2', true);
        $this->client->request(Request::METHOD_GET, self::API_ENDPOINT);
        $response = $this->client->getResponse();
        self::assertNotFalse($response->getContent());
        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['companysegments']);
        self::assertCount(2, $data['companysegments']);
    }

    public function testGetCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Segment test', 'segment-test', true);
        $this->client->request(Request::METHOD_GET, self::API_ENDPOINT.'/'.$companySegment->getId());
        $response = $this->client->getResponse();
        self::assertNotFalse($response->getContent());
        self::assertResponseIsSuccessful();
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
        $this->client->request(Request::METHOD_POST, self::API_ENDPOINT.'/new', $data);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
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
        $this->client->request(Request::METHOD_PATCH, self::API_ENDPOINT.'/'.$companySegment->getId().'/edit', $data);
        self::assertResponseIsSuccessful();
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
        $this->client->request(Request::METHOD_DELETE, self::API_ENDPOINT.'/'.$companySegment->getId().'/delete');
        self::assertResponseIsSuccessful();
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
        $this->client->request(Request::METHOD_POST, self::API_ENDPOINT.'/batch/new', $data);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
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
        $this->client->request(Request::METHOD_POST, self::API_ENDPOINT.'/batch/new', $data);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
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
        $this->client->request(Request::METHOD_PATCH, self::API_ENDPOINT.'/batch/edit', $data);
        self::assertResponseIsSuccessful();
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

    public function testAddCompanyToCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company        = $this->createCompany('Test Company', 'test@company.com');
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/company/'.$company->getId().'/add'
        );

        self::assertResponseIsSuccessful();
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        // Verify the company was actually added to the segment
        $segmentCompanyRepo = $this->em->getRepository(\Mautic\LeadBundle\Entity\SegmentCompany::class);
        $segmentCompany     = $segmentCompanyRepo->findOneBy([
            'company'        => $company,
            'companySegment' => $companySegment,
        ]);
        self::assertNotNull($segmentCompany);
        self::assertTrue($segmentCompany->isManuallyAdded());
    }

    public function testRemoveCompanyFromCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company        = $this->createCompany('Test Company', 'test@company.com');
        $this->addCompanyToCompanySegment($company, $companySegment);
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/company/'.$company->getId().'/remove'
        );

        self::assertResponseIsSuccessful();
        self::assertNotFalse($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        // Verify the company was marked as manually removed
        $this->em->clear();
        $segmentCompanyRepo = $this->em->getRepository(\Mautic\LeadBundle\Entity\SegmentCompany::class);
        $segmentCompany     = $segmentCompanyRepo->findOneBy([
            'company'        => $company,
            'companySegment' => $companySegment,
        ]);
        self::assertNotNull($segmentCompany);
        self::assertTrue($segmentCompany->isManuallyRemoved());
    }

    public function testAddCompaniesToCompanySegment(): void
    {
        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company1       = $this->createCompany('Test Company 1', 'test1@company.com');
        $company2       = $this->createCompany('Test Company 2', 'test2@company.com');
        $this->em->flush();

        $data = ['ids' => [$company1->getId(), $company2->getId()]];

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/companies/add',
            $data
        );

        self::assertResponseIsSuccessful();
        self::assertNotFalse($this->client->getResponse()->getContent());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('details', $responseData);
        self::assertIsArray($responseData['details']);
        self::assertArrayHasKey($company1->getId(), $responseData['details']);
        self::assertArrayHasKey($company2->getId(), $responseData['details']);
        self::assertTrue($responseData['details'][$company1->getId()]['success']);
        self::assertTrue($responseData['details'][$company2->getId()]['success']);

        // Verify both companies were actually added to the segment
        $segmentCompanyRepo = $this->em->getRepository(\Mautic\LeadBundle\Entity\SegmentCompany::class);
        $segmentCompany1    = $segmentCompanyRepo->findOneBy([
            'company'        => $company1,
            'companySegment' => $companySegment,
        ]);
        $segmentCompany2    = $segmentCompanyRepo->findOneBy([
            'company'        => $company2,
            'companySegment' => $companySegment,
        ]);
        self::assertNotNull($segmentCompany1);
        self::assertNotNull($segmentCompany2);
        self::assertTrue($segmentCompany1->isManuallyAdded());
        self::assertTrue($segmentCompany2->isManuallyAdded());
    }

    public function testAddCompaniesToCompanySegmentWithInvalidCompany(): void
    {
        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company        = $this->createCompany('Test Company', 'test@company.com');
        $this->em->flush();

        $invalidCompanyId = 99999;
        $data             = ['ids' => [$company->getId(), $invalidCompanyId]];

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/companies/add',
            $data
        );

        self::assertResponseIsSuccessful();
        self::assertNotFalse($this->client->getResponse()->getContent());
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('details', $responseData);
        self::assertTrue($responseData['details'][$company->getId()]['success']);
        self::assertFalse($responseData['details'][$invalidCompanyId]['success']);
    }

    public function testAddCompanyToSegmentWithoutCompanyPermission(): void
    {
        // Create user with segment permission but NO company permission
        $user = $this->createUserWithPermissions([
            'lead:lists' => 63, // Full segment permissions (view, edit, create, delete own and other)
        ]);
        $this->loginAsUser($user);

        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company        = $this->createCompany('Test Company', 'test@company.com');
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/company/'.$company->getId().'/add'
        );

        // Should get 403 because user doesn't have permission to edit the company
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRemoveCompanyFromSegmentWithoutCompanyPermission(): void
    {
        // Create user with segment permission but NO company permission
        $user = $this->createUserWithPermissions([
            'lead:lists' => 63,
        ]);
        $this->loginAsUser($user);

        $companySegment = $this->createCompanySegment('Test Segment', 'test-segment', true);
        $company        = $this->createCompany('Test Company', 'test@company.com');
        $this->addCompanyToCompanySegment($company, $companySegment);
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            self::API_ENDPOINT.'/'.$companySegment->getId().'/company/'.$company->getId().'/remove'
        );

        // Should get 403 because user doesn't have permission to edit the company
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @param array<string, int> $permissions
     */
    private function createUserWithPermissions(array $permissions): User
    {
        static $counter = 0;
        ++$counter;

        $role = $this->createRole('test_role_'.$counter);

        foreach ($permissions as $perm => $bitwise) {
            $this->createPermission($role, $perm, $bitwise);
        }

        $user = $this->createUser(
            'testuser'.$counter.'@mautic-test.com',
            'testuser'.$counter,
            'Test',
            'User '.$counter,
            $role
        );

        $this->em->flush();

        return $user;
    }

    private function loginAsUser(User $user): void
    {
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $user->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
    }
}
