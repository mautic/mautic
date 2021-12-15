<?php

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

final class ListControllerPermissionFunctionalTest extends MauticMysqlTestCase
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

    /**
     * @var LeadList
     */
    private $segmentA;

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

        $this->segmentA = $this->createSegment('Segment List A', $this->userOne);
    }

    public function testIndexPageWithCreatePermission(): void
    {
        $this->loginOtherUser($this->userOne->getUsername());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertCount(1, $crawler->filterXPath('//a[contains(@href,"/s/segments/new")]'), 'Listing page has the New button');
    }

    public function testIndexPageNonAdmin(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSegmentForUserWithoutPermission(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSegmentForUserWithPermission(): void
    {
        $this->loginOtherUser($this->userOne->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSegmentCloningUsingUserHavingPermissions(): void
    {
        $this->loginOtherUser($this->userTwo->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSegmentCloningUsingUserWithoutPermissions(): void
    {
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

        $this->loginOtherUser($userThree->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCloneInvalidSegment(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/2000');
        // For no entity found it will redirect to index page.
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/s/segments/1', $this->client->getRequest()->getRequestUri());
        $this->assertStringContainsString('No list with an id of 2000 was found!', $crawler->text());
    }

    public function testEditInvalidSegment(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/2000');
        // For no entity found it will redirect to index page.
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/s/segments/1', $this->client->getRequest()->getRequestUri());
        $this->assertStringContainsString('No list with an id of 2000 was found!', $crawler->text());
    }

    public function testEditOwnSegment(): void
    {
        $this->loginOtherUser($this->userOne->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEditOthersSegment(): void
    {
        $this->loginOtherUser($this->userTwo->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEditSegmentForUserWithoutPermission(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-edit',
            'email'         => 'user-edit@mautic-test.com',
            'first-name'    => 'user-edit',
            'last-name'     => 'user-edit',
            'role'          => [
                'name'      => 'perm_user_edit',
                'perm'      => LeadPermissions::LISTS_EDIT_OWN,
                'bitwise'   => 8,
            ],
        ]);

        $this->loginOtherUser($user->getUsername());

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteSegmentWithoutPermission(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUsername());
        $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteOthersSegmentWithPermission(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-other',
            'email'         => 'user-delete-other@mautic-test.com',
            'first-name'    => 'user-delete-other',
            'last-name'     => 'user-delete-other',
            'role'          => [
                'name'      => 'perm_user_delete_other',
                'perm'      => LeadPermissions::LISTS_DELETE_OTHER,
                'bitwise'   => 128,
            ],
        ]);
        $this->loginOtherUser($user->getUsername());
        $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteInvalidSegment()
    {
        $listId     = 99999;
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$listId);
        $this->assertStringContainsString("No list with an id of {$listId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchDeleteSegmentForDeletingSelfOthersAndNonExisting(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => LeadPermissions::LISTS_EDIT_OWN,
                'bitwise'   => 8,
            ],
        ]);

        $segment = $this->createSegment('Segment List New', $user);

        $this->loginOtherUser($user->getUsername());

        $segmentIds = [$this->segmentA->getId(), 101, $segment->getId()];
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('You do not have access to the requested area/action.', $crawler->text());
        $this->assertStringContainsString('No list with an id of 101 was found!', $crawler->text());
    }

    public function testViewSegment(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-view-own',
            'email'         => 'user-view-own@mautic-test.com',
            'first-name'    => 'user-view-own',
            'last-name'     => 'user-view-own',
            'role'          => [
                'name'      => 'perm_user_view_own',
                'perm'      => LeadPermissions::LISTS_VIEW_OWN,
                'bitwise'   => 2,
            ],
        ]);
        $segment = $this->createSegment('Segment News View', $user);

        $this->loginOtherUser($user->getUsername());
        $this->client->request(Request::METHOD_GET, '/s/segments/view/'.$segment->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->loginOtherUser($this->userOne->getUsername());
        $this->client->request(Request::METHOD_GET, '/s/segments/view/'.$segment->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testRemoveLeadFromSegmentWhereUserIsNotOwnerOfSegment(): void
    {
        $leadId = $this->createLead($this->userOne)->getId();
        $this->loginOtherUser($this->userTwo->getUsername());
        $this->client->request(Request::METHOD_POST, '/s/segments/removeLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testRemoveLeadFromSegmentWhereUserIsOwnerOfSegment(): void
    {
        $leadId = $this->createLead($this->userOne)->getId();
        $this->loginOtherUser($this->userOne->getUsername());
        $this->client->request(Request::METHOD_POST, '/s/segments/removeLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAddLeadFromInvalidLeadId(): void
    {
        $leadId     = 99999;
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/addLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertStringContainsString("No contact with an id of {$leadId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAddLeadFromInvalidSegment(): void
    {
        $listId     = 9999;
        $leadId     = $this->createLead($this->userOne)->getId();
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/addLead/'.$listId.'?leadId='.$leadId);
        $this->assertStringContainsString("No list with an id of {$listId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    private function loginOtherUser(string $name): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $this->loginUser($name);
        $this->client->setServerParameter('PHP_AUTH_USER', $name);
        $this->client->setServerParameter('PHP_AUTH_PW', 'mautic');
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

    private function createLead(User $user): Lead
    {
        $lead = new Lead();
        $lead->setCreatedByUser($user);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }
}
