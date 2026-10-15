<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Test;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\Role;

final class AutoincrementResetTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testResetPreservesExistingRoleIds(): void
    {
        $table = $this->em->getClassMetadata(Role::class)->getTableName();
        $maximumId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM '.$this->connection->quoteIdentifier($table));
        $this->assertGreaterThan(0, $maximumId);

        $this->resetAutoincrement(['roles']);
        $role = (new Role())->setName('Role after resetting auto increment');
        $this->em->persist($role);
        $this->em->flush();

        $this->assertSame($maximumId + 1, $role->getId());
    }
}
