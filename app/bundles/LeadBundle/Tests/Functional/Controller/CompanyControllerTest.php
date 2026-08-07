<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class CompanyControllerTest extends MauticMysqlTestCase
{
    public const USERNAME = 'jhony';

    public function testMergeAction(): void
    {
        $this->client->request('GET', '/s/companies/merge/1');
        $this->assertResponseIsSuccessful();
    }

    public function testMergeActionWithoutPermission(): void
    {
        $this->createAndLoginUser();
        $this->client->request('GET', '/s/companies/merge/1');
        $clientResponse         = $this->client->getResponse();
        $this->assertEquals(403, $clientResponse->getStatusCode());
    }

    public function testBatchOwnersAction(): void
    {
        $this->client->request('GET', '/s/companies/batchOwners/0');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('lead_batch_owner', (string) $this->client->getResponse()->getContent());
    }

    public function testBatchOwnersActionPost(): void
    {
        $owner = $this->createUser($this->createRole());
        $company = new Company();
        $company->setName('Batch owner company');
        $this->em->persist($company);
        $this->em->flush();

        $this->client->request('POST', '/s/companies/batchOwners/0', [
            'lead_batch_owner' => [
                'ids'      => json_encode([$company->getId()], JSON_THROW_ON_ERROR),
                'addowner' => (string) $owner->getId(),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($response['closeModal']);
        $this->assertArrayHasKey('flashes', $response);

        $this->em->clear();
        $updatedCompany = $this->em->getRepository(Company::class)->find($company->getId());
        $this->assertInstanceOf(Company::class, $updatedCompany);
        $this->assertSame($owner->getId(), $updatedCompany->getOwner()?->getId());
    }

    private function createAndLoginUser(): User
    {
        // Create non-admin role
        $role = $this->createRole();
        // Create non-admin user
        $user = $this->createUser($role);

        $this->em->flush();
        $this->em->detach($role);

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', self::USERNAME);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        return $user;
    }

    private function createRole(bool $isAdmin = false): Role
    {
        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin($isAdmin);

        $this->em->persist($role);

        return $role;
    }

    private function createUser(Role $role): User
    {
        $user = new User();
        $user->setFirstName('Jhony');
        $user->setLastName('Doe');
        $user->setUsername(self::USERNAME);
        $user->setEmail('john.doe@email.com');
        $hasher = self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
