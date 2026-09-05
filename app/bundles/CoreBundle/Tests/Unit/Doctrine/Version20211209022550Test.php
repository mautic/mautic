<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\Migrations\Version20211209022550;
use Mautic\UserBundle\Entity\Role;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class Version20211209022550Test extends TestCase
{
    public function testMigratesRolesReturnedWithScalarHydrationData(): void
    {
        $role = new Role();
        $role->setRawPermissions([
            'lead:leads' => ['viewown'],
            'lead:lists' => [],
        ]);

        $roleModel = new class($role) {
            public function __construct(private Role $role)
            {
            }

            public function getEntities(array $args = []): array
            {
                return [[$this->role, 1]];
            }
        };

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap([
            [\Mautic\UserBundle\Model\RoleModel::class, $roleModel],
            ['doctrine.orm.entity_manager', $entityManager],
        ]);

        $migration = new Version20211209022550($this->createStub(Connection::class), new NullLogger());
        $migration->setContainer($container);

        $migration->postUp($this->createStub(Schema::class));

        $this->assertSame(['viewown', 'create'], $role->getRawPermissions()['lead:lists']);
    }
}
