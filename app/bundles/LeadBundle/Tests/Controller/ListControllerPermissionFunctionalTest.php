<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class ListControllerPermissionFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var User
     */
    private $nonAdminUser;

    /**
     * @var User
     */
    private $userOne;

    /**
     * @var User
     */
    private $userTwo;

    protected function setUp(): void
    {
        parent::setUp();

        // A user without any segment permissions.
        $this->nonAdminUser = $this->createUser([
            'user-name'     => 'non-admin',
            'email'         => 'non-admin@mautic-test.com',
            'first-name'    => 'non-admin',
            'last-name'     => 'non-admin',
            'role'          => [
                'name'      => 'perm_non_admin',
                'perm'      => 'core:themes.full',
                'bitwise'   => 1024,
            ],
        ]);

        // A user without any segment create, view own, edit own.
        $this->userOne = $this->createUser(
            [
                'user-name'     => 'user-one',
                'email'         => 'user-one@mautic-test.com',
                'first-name'    => 'user-one',
                'last-name'     => 'user-one',
                'role'          => [
                    'name'      => 'perm_user_one',
                    'perm'      => LeadPermissions::LISTS_CREATE,
                    'bitwise'   => 32,
                ],
            ]
        );

        // A user without any segment view own/others, edit own/others and delete own/others.
        $this->userTwo = $this->createUser([
            'user-name'     => 'user-two',
            'email'         => 'user-two@mautic-test.com',
            'first-name'    => 'user-two',
            'last-name'     => 'user-two',
            'role'          => [
                'name'      => 'perm_user_two',
                'perm'      => LeadPermissions::LISTS_EDIT_OTHER,
                'bitwise'   => 16,
            ],
        ]);
    }

    public function testIndexPageWithCreatePermission(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($this->userOne->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $this->userOne->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertCount(1, $crawler->filterXPath('//a[contains(@href,"/s/segments/new")]'), 'Listing page has the New button');
    }

    public function testIndexPageNonAdmin(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($this->nonAdminUser->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $this->nonAdminUser->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testUserWithoutPermissionCreatingNewSegments(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($this->nonAdminUser->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $this->nonAdminUser->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testUserWithPermissionCreatingNewSegments(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($this->userOne->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $this->userOne->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSegmentCloningUsingUserHavingPermissions(): void
    {
        $segment = $this->createSegment('Segment List', $this->userOne);

        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($this->userTwo->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $this->userTwo->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$segment->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSegmentCloningUsingUserWithoutPermissions(): void
    {
        $segment = $this->createSegment('Segment List', $this->userOne);

        $userThree = $this->createUser(
            [
                'user-name'     => 'user-3',
                'email'         => 'user-3@mautic-test.com',
                'first-name'    => 'user-3',
                'last-name'     => 'user-3',
                'role'          => [
                    'name'      => 'perm_user_three',
                    'perm'      => LeadPermissions::LISTS_DELETE_OTHER,
                    'bitwise'   => 32,
                ],
            ]
        );

        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($userThree->getUsername());
        $this->client->setServerParameter('PHP_AUTH_USER', $userThree->getUsername());
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$segment->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, mixed> $userDetails
     */
    private function createUser(array $userDetails): User
    {
        $role = new Role();
        $role->setName($userDetails['role']['name']);
        $role->setIsAdmin(false);

        $this->em->persist($role);

        $this->createPermission($role, $userDetails['role']['perm'], $userDetails['role']['bitwise']);

        $user = new User();
        $user->setEmail($userDetails['email']);
        $user->setUsername($userDetails['user-name']);
        $user->setFirstName($userDetails['first-name']);
        $user->setLastName($userDetails['last-name']);
        $user->setRole($role);

        /** @var PasswordEncoderInterface $encoder */
        $encoder = $this->container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('mautic', $user->getSalt()));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createPermission(Role $role, string $rawPermission, int $bitwise): void
    {
        $parts      = explode(':', $rawPermission);
        $permission = new Permission();
        $permission->setBundle($parts[0]);
        $permission->setName($parts[1]);
        $permission->setRole($role);
        $permission->setBitwise($bitwise);
        $this->em->persist($permission);
    }

    private function createSegment(string $name, User $user): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setAlias(str_shuffle('abcdefghijklmnopqrstuvwxyz'));
        $segment->setCreatedBy($user);
        $this->em->persist($segment);
        $this->em->flush();

        return $segment;
    }
}
