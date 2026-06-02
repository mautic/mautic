<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\ApiPlatform;

use Mautic\LeadBundle\Entity\Company;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class CompanyOwnershipApiV2AuthorizationRegressionTest extends OwnershipScopedApiAuthorizationTestBase
{
    public function testViewOwnCollectionCannotSeeForeignCompanyOnApiV2(): void
    {
        $ownerUser    = $this->createUserWithPermissions(
            username: 'owner.user',
            email: 'owner.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother', 'editother', 'deleteother'],
                'api:access' => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.user',
            email: 'restricted.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        $ownerCompany = new Company();
        $ownerCompany->setName('Owner Company');
        $ownerCompany->setOwner($restrictedUser);
        $ownerCompany->setCreatedBy($restrictedUser);

        $foreignCompany = new Company();
        $foreignCompany->setName('Foreign Company');
        $foreignCompany->setOwner($ownerUser);
        $foreignCompany->setCreatedBy($ownerUser);

        $this->em->persist($ownerCompany);
        $this->em->persist($foreignCompany);
        $this->em->flush();
        $this->em->clear();

        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/companies?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $companies = $data['member'];
        self::assertCount(1, $companies, 'Expected exactly 1 company');
        self::assertSame($ownerCompany->getId(), $companies[0]['id']);
        self::assertSame('Owner Company', $companies[0]['name']);
    }

    public function testViewOwnCollectionReportsOwnedTotalAcrossPagesOnApiV2(): void
    {
        $ownerUser    = $this->createUserWithPermissions(
            username: 'owner.user',
            email: 'owner.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewother', 'editother', 'deleteother'],
                'api:access' => ['full'],
            ]
        );
        $restrictedUser = $this->createUserWithPermissions(
            username: 'restricted.user',
            email: 'restricted.user@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        // Create 4 companies owned by restricted user
        for ($i = 1; $i <= 4; ++$i) {
            $company = new Company();
            $company->setName(sprintf('Restricted Company %d', $i));
            $company->setOwner($restrictedUser);
            $company->setCreatedBy($restrictedUser);
            $this->em->persist($company);
        }

        // Create 12 companies owned by owner user (not visible to restricted user)
        for ($i = 1; $i <= 12; ++$i) {
            $company = new Company();
            $company->setName(sprintf('Owner Company %d', $i));
            $company->setOwner($ownerUser);
            $company->setCreatedBy($ownerUser);
            $this->em->persist($company);
        }

        $this->em->flush();
        $this->em->clear();

        $restrictedUser = $this->em->getRepository(User::class)->findOneBy(['username' => 'restricted.user']);
        \assert($restrictedUser instanceof User);
        $this->loginUser($restrictedUser);
        $this->client->setServerParameter('PHP_AUTH_USER', $restrictedUser->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        // Request page 1 with all items on one page first
        $this->client->request('GET', '/api/v2/companies?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $page1Data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('member', $page1Data);
        self::assertArrayHasKey('totalItems', $page1Data);
        self::assertSame(4, $page1Data['totalItems'], 'Should report totalItems 4 owned companies');
        self::assertCount(4, $page1Data['member'], 'Should return all 4 owned companies on single page');

        // Verify all items belong to restricted user
        foreach ($page1Data['member'] as $company) {
            self::assertStringStartsWith('Restricted Company', $company['name']);
        }
    }

    public function testViewOwnCollectionRespectsOwnerFieldAfterReassignmentOnApiV2(): void
    {
        $originalOwner = $this->createUserWithPermissions(
            username: 'original.owner',
            email: 'original.owner@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );
        $newOwner = $this->createUserWithPermissions(
            username: 'new.owner',
            email: 'new.owner@example.test',
            password: 'Maut1cR0cks!',
            permissions: [
                'lead:leads' => ['viewown', 'editown', 'deleteown'],
                'api:access' => ['full'],
            ]
        );

        // Create a company where originalOwner is both creator and owner
        $company = new Company();
        $company->setName('Reassigned Company');
        $company->setCreatedBy($originalOwner);
        $company->setOwner($originalOwner);
        $this->em->persist($company);
        $this->em->flush();

        // Reassign to newOwner (owner changes, but createdBy stays originalOwner)
        $company->setOwner($newOwner);
        $this->em->persist($company);
        $this->em->flush();
        $this->em->clear();

        // Test 1: newOwner (current owner) SHOULD see the company
        $newOwner = $this->em->getRepository(User::class)->findOneBy(['username' => 'new.owner']);
        \assert($newOwner instanceof User);
        $this->loginUser($newOwner);
        $this->client->setServerParameter('PHP_AUTH_USER', $newOwner->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/companies?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(
            1,
            $data['member'],
            'New owner should see the reassigned company (owner field takes precedence over createdBy)'
        );
        self::assertSame($company->getId(), $data['member'][0]['id']);

        // Test 2: originalOwner (creator but no longer owner) should NOT see the company
        $originalOwner = $this->em->getRepository(User::class)->findOneBy(['username' => 'original.owner']);
        \assert($originalOwner instanceof User);
        $this->loginUser($originalOwner);
        $this->client->setServerParameter('PHP_AUTH_USER', $originalOwner->getUserIdentifier());
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request('GET', '/api/v2/companies?page=1&itemsPerPage=10');
        $response = $this->client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        $data = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(
            0,
            $data['member'],
            'Original creator should NOT see the company after reassignment (owner field takes precedence)'
        );
    }
}
