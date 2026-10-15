<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class PostgreSqlSchemaCompatibilityTest extends MauticMysqlTestCase
{
    public function testApplicationSchemaMatchesEntityMetadata(): void
    {
        if (!$this->isPostgresqlPlatform()) {
            $this->markTestSkipped('PostgreSQL schema introspection compatibility.');
        }

        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        $this->assertSame([], (new SchemaTool($this->em))->getUpdateSchemaSql($metadata, true));
    }
}
