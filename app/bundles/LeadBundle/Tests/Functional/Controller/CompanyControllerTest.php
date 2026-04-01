<?php

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class CompanyControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public const USERNAME = 'jhony';

    public function testSearchByCompanySegment(): void
    {
        $segment = $this->createCompanySegment('Tech Companies', 'tech-companies');
        $this->em->persist($segment);

        $companyInSegment1   = $this->createCompany('Company A', 'a@example.com');
        $companyInSegment2   = $this->createCompany('Company B', 'b@example.com');
        $companyNotInSegment = $this->createCompany('Company C', 'c@example.com');

        $this->em->persist($companyInSegment1);
        $this->em->persist($companyInSegment2);
        $this->em->persist($companyNotInSegment);
        $this->em->flush();

        $this->addCompanyToCompanySegment($companyInSegment1, $segment);
        $this->addCompanyToCompanySegment($companyInSegment2, $segment);

        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/companies?search=segment:tech-companies');
        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('#companyTable tbody tr');
        $this->assertCount(2, $rows, 'Should display exactly 2 companies in the segment');

        $rowTexts = [];
        $rows->each(function ($row) use (&$rowTexts) {
            $rowTexts[] = $row->text();
        });

        $combinedText = implode(' ', $rowTexts);
        $this->assertStringContainsString('Company A', $combinedText);
        $this->assertStringContainsString('Company B', $combinedText);
        $this->assertStringNotContainsString('Company C', $combinedText);
    }

    public function testSearchByNonExistentCompanySegment(): void
    {
        $company = $this->createCompany('Test Company', 'test@example.com');
        $this->em->persist($company);
        $this->em->flush();

        $this->em->clear();

        $crawler = $this->client->request('GET', '/s/companies?search=segment:non-existent-segment');
        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('No Results Found', $content);
    }

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
        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        $this->assertInstanceOf(PasswordHasherInterface::class, $hasher);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));
        $user->setRole($role);

        $this->em->persist($user);

        return $user;
    }
}
