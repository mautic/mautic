<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserIsGuest(): void
    {
        $user = new User(true);
        $this->assertTrue($user->isGuest());
    }

    public function testUserIsNotGuest(): void
    {
        $user = new User();
        $this->assertFalse($user->isGuest());
    }

    public function testFirstNameChange(): void
    {
        $user = new User();
        $user->setFirstName('John');
        Assert::assertEquals(['firstName' => [null, 'John']], $user->getChanges());
    }

    /**
     * @dataProvider roleProvider
     * 
     * @param mixed[] $expectedChanges
     */
    public function testRoleChange(?Role $currentRole, ?Role $newRole, array $expectedChanges): void
    {
        $user = new User();
        $user->setRole($currentRole);
        $user->setRole($newRole);
        Assert::assertEquals($expectedChanges, $user->getChanges());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function roleProvider(): iterable
    {
        yield [
            null,
            null,
            [],
        ];

        yield [
            (new RoleFake(11))->setName('role1'),
            null,
            ['role' => ['role1 (11)', null]],
        ];

        yield [
            (new RoleFake(11))->setName('role1'),
            (new RoleFake(345))->setName('role3'),
            ['role' => ['role1 (11)', 'role3 (345)']],
        ];

        yield [
            (new RoleFake(11))->setName('role1'),
            (new RoleFake(11))->setName('role1'),
            [],
        ];

        yield [
            null,
            (new RoleFake(11))->setName('role1'),
            ['role' => [null, 'role1 (11)']],
        ];
    }
}
