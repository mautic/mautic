<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

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

        $this->nonAdminUser = $this->createUser([
            'user-name'     => 'non-admin',
            'email'         => 'non-admin@mautic-test.com',
            'first-name'    => 'non-admin',
            'last-name'     => 'non-admin',
            'role'          => [
                'name'      => 'perm_non_admin',
                'perm'      => 'core:themes',
                'bitwise'   => 1024,
            ],
        ]);

        $this->userOne = $this->createUser(
            [
                'user-name'     => 'user-one',
                'email'         => 'user-one@mautic-test.com',
                'first-name'    => 'user-one',
                'last-name'     => 'user-one',
                'role'          => [
                    'name'      => 'perm_user_one',
                    'perm'      => 'lead:lists',
                    'bitwise'   => 40,
                ],
            ]
        );

        $this->userTwo = $this->createUser([
            'user-name'     => 'user-two',
            'email'         => 'user-two@mautic-test.com',
            'first-name'    => 'user-two',
            'last-name'     => 'user-two',
            'role'          => [
                'name'      => 'perm_user_two',
                'perm'      => 'lead:lists',
                'bitwise'   => 16,
            ],
        ]);

        $this->segmentA = $this->createSegment('Segment List A', $this->userOne);
    }

    public function testIndexPageWithCreatePermission(): void
    {
        $this->loginOtherUser($this->userOne->getUserIdentifier());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertCount(1, $crawler->filterXPath('//a[contains(@href,"/s/segments/new")]'), 'Listing page has the New button');
    }

    public function testIndexPageNonAdmin(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexPageForPaging(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/segments/2');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSegmentForUserWithoutPermission(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateSegmentForUserWithPermission(): void
    {
        $this->loginOtherUser($this->userOne->getUserIdentifier());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Submitting for cancel button click.
        $form           = $crawler->selectButton('Cancel')->form();
        $crawlerCancel  = $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Contact Segments', $crawlerCancel->html());

        // Save the Segment.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/new');
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[name]']->setValue('Segment Test');
        $form['leadlist[alias]']->setValue('segment_test');
        $form['leadlist[isPublished]']->setValue('0');
        $crawler = $this->client->submit($form);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Edit Segment - Segment Test', $crawler->html());
    }

    /**
     * @dataProvider dataSegmentCloneUserPermissions
     */
    public function testSegmentCloningOwnedSegmentWithDifferentPermissions(string $name, int $perm, int $expected): void
    {
        $user = $this->createUser(
            [
                'user-name'     => $name,
                'email'         => $name.'@mautic-test.com',
                'first-name'    => $name,
                'last-name'     => $name,
                'role'          => [
                    'name'      => 'perm_user_three',
                    'perm'      => 'lead:lists',
                    'bitwise'   => $perm, // Create and View own
                ],
            ]
        );
        $this->loginOtherUser($user->getUserIdentifier());

        $segment = $this->createSegment('Test Segment for clone test', $user);

        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$segment->getId());
        $this->assertEquals($expected, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, mixed[]>
     */
    public function dataSegmentCloneUserPermissions(): iterable
    {
        yield 'Only create' => ['user-clone-1', 32, Response::HTTP_FORBIDDEN];
        yield 'Create and View own' => ['user-clone-2', 34, Response::HTTP_OK];
        yield 'Create and View other' => ['user-clone-2', 36, Response::HTTP_FORBIDDEN];
    }

    public function testSegmentCloningUsingUserHavingPermissions(): void
    {
        $user = $this->createUser(
            [
                'user-name'     => 'user-3',
                'email'         => 'user-3@mautic-test.com',
                'first-name'    => 'user-3',
                'last-name'     => 'user-3',
                'role'          => [
                    'name'      => 'perm_user_three',
                    'perm'      => 'lead:lists',
                    'bitwise'   => 36, // Create and view other
                ],
            ]
        );
        $this->loginOtherUser($user->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testSegmentCloningUsingUserWithoutPermissions(): void
    {
        $this->loginOtherUser($this->userTwo->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments/clone/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testCloneInvalidSegment(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/clone/2000');
        // For no entity found it will redirect to index page.
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/s/segments/1', $this->client->getRequest()->getRequestUri());
        $this->assertStringContainsString('No segment with an id of 2000 was found!', $crawler->text());
    }

    public function testEditSegmentAndClickOnButtons(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Submitting for cancel button click.
        $form           = $crawler->selectButton('Cancel')->form();
        $crawlerCancel  = $this->client->submit($form);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($this->segmentA->getName(), $crawlerCancel->html());

        // Save the Segment.
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('0');
        $crawler = $this->client->submit($form);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Edit Segment - '.$this->segmentA->getName(), $crawler->html());
    }

    public function testEditInvalidSegment(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/2000');
        // For no entity found it will redirect to index page.
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('/s/segments/1', $this->client->getRequest()->getRequestUri());
        $this->assertStringContainsString('No segment with an id of 2000 was found!', $crawler->text());
    }

    public function testEditOwnSegment(): void
    {
        $this->loginOtherUser($this->userOne->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEditOthersSegment(): void
    {
        $this->loginOtherUser($this->userTwo->getUserIdentifier());

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
                'perm'      => 'lead:lists',
                'bitwise'   => 8,
            ],
        ]);

        $this->loginOtherUser($user->getUserIdentifier());

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testEditSegmentWhileLock(): void
    {
        $segmentA = $this->segmentA;
        $segmentA->setCheckedOut(new \DateTime());
        $segmentA->setCheckedOutBy($this->userOne);
        $this->em->persist($segmentA);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // As $segmentA is locked, so it will redirect user to its view page.
        $this->assertStringContainsString('/s/segments/view/'.$segmentA->getId(), $this->client->getRequest()->getRequestUri());
    }

    public function testDeleteSegmentWithoutPermission(): void
    {
        $this->loginOtherUser($this->nonAdminUser->getUserIdentifier());
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
                'perm'      => 'lead:lists',
                'bitwise'   => 128,
            ],
        ]);
        $this->loginOtherUser($user->getUserIdentifier());
        $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$this->segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteSegmentWithDependencyAndLockedInWithOtherUser(): void
    {
        $listId = $this->segmentA->getId();
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$listId],
            ],
            'display'   => '',
            'filter'    => [$listId],
        ]];
        $segmentA  = $this->createSegment('Segment List A', $this->userTwo, $filter);

        $this->assertSame($filter, $segmentA->getFilters(), 'Filters');
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$listId);
        $this->assertStringContainsString("Segment cannot be deleted, it is required by {$segmentA->getName()}.", $crawler->text());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $segmentA->setCheckedOut(new \DateTime());
        $segmentA->setCheckedOutBy($this->userOne);
        $this->em->persist($segmentA);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$segmentA->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertStringContainsString("{$segmentA->getName()} is currently checked out by", $crawler->html());

        // As $segmentA is locked, so it will redirect user to its view page.
        $this->assertStringContainsString('/s/segments/1', $this->client->getRequest()->getRequestUri());
    }

    public function testDeleteInvalidSegment(): void
    {
        $listId     = 99999;
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/delete/'.$listId);
        $this->assertStringContainsString("No segment with an id of {$listId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchDeleteSegmentWhenUserDoNotHavePermission(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => 'lead:lists',
                'bitwise'   => 82,
            ],
        ]);

        $this->loginOtherUser($user->getUserIdentifier());

        $segmentIds = [
            $this->segmentA->getId(),
        ];

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // The logged-in user do not have permission to delete the segment $this->segmentA.
        $this->assertStringContainsString('You do not have access to the requested area/action.', $crawler->text());
    }

    public function testBatchDeleteSegmentWhenUserDoNotHavePermissionAndSegmentIsInvalid(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => 'lead:lists',
                'bitwise'   => 82,
            ],
        ]);

        $this->loginOtherUser($user->getUserIdentifier());

        $segmentIds = [
            101,
        ];

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // The segment 101 is invalid.
        $this->assertStringContainsString('No segment with an id of 101 was found!', $crawler->text());
    }

    public function testBatchDeleteSegmentWhenUserHavePermission(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => 'lead:lists',
                'bitwise'   => 82,
            ],
        ]);

        $segmentA  = $this->createSegment('Segment List A', $user);
        $this->em->flush();

        $this->loginOtherUser($user->getUserIdentifier());

        $segmentIds = [
            $segmentA->getId(),
        ];

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // Only one segments is deleted.
        $this->assertStringContainsString('1 segments have been deleted!', $crawler->html());
    }

    public function testBatchDeleteSegmentWhenDeletingLocked(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => 'lead:lists',
                'bitwise'   => 82,
            ],
        ]);

        $segmentC = $this->createSegment('Segment List C', $user);
        $segmentC->setCheckedOut(new \DateTime());
        $segmentC->setCheckedOutBy($this->userOne);
        $this->em->persist($segmentC);
        $this->em->flush();

        $this->loginOtherUser($user->getUserIdentifier());

        $segmentIds = [
            $segmentC->getId(),
        ];

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // The segment $segmentC is being locked by user other than logged-in.
        $this->assertStringContainsString("{$segmentC->getName()} is currently checked out by", $crawler->html());
    }

    public function testBatchDeleteSegmentWhenDeletingRequiredByOthers(): void
    {
        $user = $this->createUser([
            'user-name'     => 'user-delete-a',
            'email'         => 'user-delete-a@mautic-test.com',
            'first-name'    => 'user-delete-a',
            'last-name'     => 'user-delete-a',
            'role'          => [
                'name'      => 'perm_user_delete_a',
                'perm'      => 'lead:lists',
                'bitwise'   => 82,
            ],
        ]);

        $segmentA  = $this->createSegment('Segment List A', $user);

        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$segmentA->getId()],
            ],
            'display'   => '',
            'filter'    => [$segmentA->getId()],
        ]];

        $segmentB = $this->createSegment('Segment List with filter', $user, $filter);
        $this->assertSame($filter, $segmentB->getFilters(), 'Filters');

        $this->loginOtherUser($user->getUserIdentifier());

        $segmentIds = [
            $segmentA->getId(),
        ];

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/batchDelete?ids='.json_encode($segmentIds));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        // The segment $segmentA is used as filter in $segmentB.
        $this->assertStringContainsString("{$segmentA->getName()} cannot be deleted, it is required by other segments.", $crawler->text());
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
                'perm'      => 'lead:lists',
                'bitwise'   => 2,
            ],
        ]);
        $segment = $this->createSegment('Segment News View', $user);

        $this->loginOtherUser($user->getUserIdentifier());
        $this->client->request(Request::METHOD_GET, '/s/segments/view/'.$segment->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->loginOtherUser($this->userOne->getUserIdentifier());
        $this->client->request(Request::METHOD_GET, '/s/segments/view/'.$segment->getId());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testPostOnViewSegment(): void
    {
        $this->client->request(Request::METHOD_POST, '/s/segments/view/'.$this->segmentA->getId(), [
            'includeEvents' => [
                'manually_added',
                'manually_removed',
                'filter_added',
            ],
        ]);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testRemoveLeadFromSegmentWhereUserIsNotOwnerOfSegment(): void
    {
        $leadId = $this->createLead($this->userOne)->getId();
        $this->loginOtherUser($this->userTwo->getUserIdentifier());
        $this->client->request(Request::METHOD_POST, '/s/segments/removeLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testRemoveLeadFromSegmentWhereUserIsOwnerOfSegment(): void
    {
        $leadId = $this->createLead($this->userOne)->getId();
        $this->loginOtherUser($this->userOne->getUserIdentifier());
        $this->client->request(Request::METHOD_POST, '/s/segments/removeLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    public function testAddLeadToSegmentForInvalidLeadAndLockedLeadAndInvalidSegment(): void
    {
        $leadId     = 99999;
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/addLead/'.$this->segmentA->getId().'?leadId='.$leadId);
        $this->assertStringContainsString("No contact with an id of {$leadId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $listId     = 9999;
        $lead       = $this->createLead($this->userOne);
        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/addLead/'.$listId.'?leadId='.$lead->getId());
        $this->assertStringContainsString("No segment with an id of {$listId} was found!", $crawler->html());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $lead->setCheckedOut(new \DateTime());
        $lead->setCheckedOutBy($this->userOne);
        $this->em->persist($lead);
        $this->em->flush();

        $crawler    = $this->client->request(Request::METHOD_POST, '/s/segments/addLead/'.$this->segmentA->getId().'?leadId='.$lead->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertStringContainsString("{$lead->getPrimaryIdentifier()} is currently checked out by", $crawler->html());
    }

    private function loginOtherUser(string $name): void
    {
        $this->client->request(Request::METHOD_GET, '/s/logout');
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $name]);

        $this->loginUser($user);
        $this->client->setServerParameter('PHP_AUTH_USER', $name);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');
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

        $hasher = self::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);
        $user->setPassword($hasher->hash('Maut1cR0cks!'));

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

    /**
     * @param mixed[] $filters
     */
    private function createSegment(string $name, User $user, array $filters = []): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(str_shuffle('abcdefghijklmnopqrstuvwxyz'));
        $segment->setCreatedBy($user);

        if ($filters) {
            $segment->setFilters($filters);
        }

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
