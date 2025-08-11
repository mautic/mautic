<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class ToggleContactCampaignFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const PASS                    = 'Maut1cR0cks!';
    private const PERMISSION_CAMPAIGN_OWN = 298;
    private const PERMISSION_LEAD_OWN     = 1024;

    public function testContactCampaignToggleModal(): void
    {
        $admin = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $lead  = $this->createLead('John', 'Doe', 'john-doe@mautic.org');

        // Campaign by admin
        $campaignOne = $this->createCampaign('Campaign By Admin')
            ->setCreatedByUser($admin);
        $this->em->persist($campaignOne);

        // Role + user with "own" permission
        $roleOwn = $this->createRoleWithPermissions(
            'user_with_own_campaign',
            [
                ['campaign', 'campaigns', self::PERMISSION_CAMPAIGN_OWN],
                ['lead', 'leads', self::PERMISSION_LEAD_OWN],
            ]
        );

        $userOwn = $this->createUserWithRole(
            'user-with-own-campaign@mautic-test.com',
            'user-with-own-campaign',
            $roleOwn
        );

        $this->em->flush();

        // Campaign by other user
        $campaignTwo = $this->createCampaign('Campaign By Another user')
            ->setCreatedBy($userOwn);
        $this->em->persist($campaignTwo);

        // Flush all at once
        $this->em->flush();
        $this->em->clear();

        // Login as non-admin user
        $this->loginAs($userOwn->getUsername());

        // Open modal
        $crawler = $this->client->request(
            Request::METHOD_GET,
            '/s/contacts/campaign/'.$lead->getId()
        );

        // Verify only own campaign is visible
        $this->assertCount(1, $crawler->filterXPath("//li[contains(@class, 'list-group-item')]"));
    }

    private function loginAs(string $username): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $username);
        $this->client->setServerParameter('PHP_AUTH_PW', self::PASS);
    }

    /**
     * @param array<int, array<int|string>> $permissions
     */
    private function createRoleWithPermissions(string $name, array $permissions): Role
    {
        $role = new Role();
        $role->setName($name)->setIsAdmin(false);
        $this->em->persist($role);

        foreach ($permissions as [$bundle, $permName, $bitwise]) {
            $perm = (new Permission())
                ->setBundle($bundle)
                ->setName($permName)
                ->setRole($role)
                ->setBitwise($bitwise);
            $this->em->persist($perm);
        }
        $this->em->flush();

        return $role;
    }

    private function createUserWithRole(string $email, string $username, Role $role): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setUsername($username)
            ->setFirstName($username)
            ->setLastName($username)
            ->setRole($role);

        $hasher = self::getContainer()
            ->get('security.password_hasher_factory')
            ->getPasswordHasher($user);

        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash(self::PASS));

        $this->em->persist($user);

        return $user;
    }
}
