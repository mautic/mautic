<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class Issue17912FunctionalTest extends AbstractMauticTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public function testInaccessibleChildrenAreOmittedAndInaccessibleRootsAreDenied(): void
    {
        $admin        = $this->getUser('admin');
        $user         = $this->createRestrictedUser();
        $root         = $this->createSegment('Issue 17912 root '.uniqid(), $user);
        $accessible    = $this->createSegment('Issue 17912 accessible '.uniqid(), $user);
        $inaccessible = $this->createSegment('Issue 17912 inaccessible '.uniqid(), $admin);
        $grandchild   = $this->createSegment('Issue 17912 grandchild '.uniqid(), $user);
        $this->em->flush();
        $root->setFilters([[
            'type'       => 'leadlist',
            'properties' => ['filter' => [$accessible->getId(), $inaccessible->getId()]],
        ]]);
        $accessible->setFilters([[
            'type'       => 'leadlist',
            'properties' => ['filter' => [$grandchild->getId()]],
        ]]);
        $this->em->flush();

        $this->loginUser($user);
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getSegmentDependencyTree&id='.$root->getId());
        Assert::assertSame(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $nodes    = array_merge(...array_map(static fn (array $level): array => $level['nodes'], $response['levels']));
        $names    = array_column($nodes, 'name');

        Assert::assertContains($accessible->getName(), $names);
        Assert::assertContains($grandchild->getName(), $names);
        Assert::assertNotContains($inaccessible->getName(), $names);
        Assert::assertSame(3, count($nodes));
        restore_error_handler();
    }

    public function testUnauthorizedRootIsRejected(): void
    {
        $admin = $this->getUser('admin');
        $user  = $this->createRestrictedUser();
        $root  = $this->createSegment('Issue 17912 unauthorized root '.uniqid(), $admin);
        $this->em->flush();

        $this->loginUser($user);
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getSegmentDependencyTree&id='.$root->getId());

        Assert::assertSame(403, $this->client->getResponse()->getStatusCode());
        Assert::assertStringNotContainsString('dependency-tree', (string) $this->client->getResponse()->getContent());
    }

    public function testNonOwnerWithOtherListPermissionCanViewRoot(): void
    {
        $admin = $this->getUser('admin');
        $user  = $this->createUserWithPermissions(['lead:lists' => ['viewother']]);
        $root  = $this->createSegment('Issue 17912 other-owner root '.uniqid(), $admin);
        $this->em->flush();

        $this->loginUser($user);
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getSegmentDependencyTree&id='.$root->getId());

        Assert::assertSame(200, $this->client->getResponse()->getStatusCode());
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        Assert::assertSame($root->getName(), $response['levels'][0]['nodes'][0]['name']);
    }

    private function createRestrictedUser(): User
    {
        return $this->createUserWithPermissions(['lead:lists' => ['viewown']]);
    }

    /**
     * @param array<string,array<int,string>> $permissions
     */
    private function createUserWithPermissions(array $permissions): User
    {
        $role = new Role();
        $role->setName('Issue 17912 restricted role '.uniqid());
        $role->setIsAdmin(false);
        self::getContainer()->get(RoleModel::class)->setRolePermissions($role, $permissions);
        $this->em->persist($role);

        $user = new User();
        $user->setFirstName('Issue');
        $user->setLastName('17912');
        $user->setUsername('issue17912_'.uniqid());
        $user->setEmail($user->getUsername().'@example.invalid');
        $user->setRole($role);
        $user->setPassword(self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher($user)->hash('mautic'));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createSegment(string $name, User $owner): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias(strtolower(str_replace(' ', '-', $name)));
        $segment->setCreatedBy($owner);
        $this->em->persist($segment);

        return $segment;
    }

    private function getUser(string $username): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        Assert::assertInstanceOf(User::class, $user);

        return $user;
    }
}
