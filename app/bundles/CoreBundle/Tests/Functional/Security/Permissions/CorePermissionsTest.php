<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Security\Permissions;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Security\Permissions\VirtualPermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;

class CorePermissionsTest extends MauticMysqlTestCase
{
    /**
     * @return iterable<array{bool}>
     */
    public function dataVirtualPermission(): iterable
    {
        yield 'Permission granted' => [true];
        yield 'Permission declined' => [false];
    }

    /**
     * @dataProvider dataVirtualPermission
     */
    public function testVirtualPermission(bool $grant): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'sales']);
        $this->loginUser($user);
        $permissions = self::getContainer()->get('mautic.security');
        \assert($permissions instanceof CorePermissions);
        $permissions->setPermissionObject($this->createVirtualPermission($grant));

        Assert::assertSame($grant, $permissions->isGranted('test:group:action', 'MATCH_ALL', $user));
    }

    private function createVirtualPermission(bool $grant): AbstractPermissions
    {
        $permission = new class([]) extends AbstractPermissions implements VirtualPermissions {
            public bool $grant;

            public function getName(): string
            {
                return 'test';
            }

            public function isSupported($name, $level = ''): bool
            {
                Assert::assertSame('group', $name);
                Assert::assertSame('action', $level);

                return true;
            }

            /**
             * @param mixed[] $userPermissions
             */
            public function isGranted($userPermissions, $name, $level): bool
            {
                throw new \BadMethodCallException('This method should not be invoked.');
            }

            public function isEnabled(): bool
            {
                return false;
            }

            public function isVirtuallyGranted(string $name, string $level): bool
            {
                Assert::assertSame('group', $name);
                Assert::assertSame('action', $level);

                return $this->grant;
            }
        };

        $permission->grant = $grant;

        return $permission;
    }
}
