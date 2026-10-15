<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Mautic\CoreBundle\DependencyInjection\Compiler\DbalPass;
use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\CoreBundle\Test\EnvLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class PostgreSqlIdentifierGenerationTest extends TestCase
{
    private Connection $connection;
    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL cold identifier metadata.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => $_ENV['DB_DRIVER'], 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'identifier_metadata_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    #[DataProvider('identifierTypes')]
    public function testColdAutoMetadataPreservesSerialDefaultAndGeneratesIds(string $type, string $serialType, int|string $firstId): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('doctrine.orm.default_configuration', new Definition(Configuration::class));
        (new DbalPass())->process($container);
        $configuration = ORMSetup::createConfiguration(true);
        foreach ($container->getDefinition('doctrine.orm.default_configuration')->getMethodCalls() as [$method, $arguments]) {
            $configuration->{$method}(...$arguments);
        }
        $driver = $this->createStub(MappingDriver::class);
        $driver->method('getAllClassNames')->willReturn([PostgreSqlIdentifierFixture::class]);
        $driver->method('isTransient')->willReturn(false);
        $driver->method('loadMetadataForClass')->willReturnCallback(static function (string $className, ClassMetadata $metadata) use ($type): void {
            $metadata->setPrimaryTable(['name' => 'serial_fixture']);
            $metadata->mapField(['fieldName' => 'id', 'type' => $type, 'id' => true]);
            $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
            $metadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        });
        $configuration->setMetadataDriverImpl($driver);
        $events = new EventManager();
        $events->addEventListener(Events::loadClassMetadata, new DoctrineEventsSubscriber('fixture_'));
        $em = new EntityManager($this->connection, $configuration, $events);
        $metadata = $em->getClassMetadata(PostgreSqlIdentifierFixture::class);
        $this->assertSame(ClassMetadata::GENERATOR_TYPE_IDENTITY, $metadata->generatorType);
        $target = (new SchemaTool($em))->getSchemaFromMetadata([$metadata])->getTable('fixture_serial_fixture');
        $this->assertTrue($target->getColumn('id')->getAutoincrement());

        // This is the SERIAL layout created during installation before a PostgreSQL cache rebuild.
        $this->connection->executeStatement('CREATE TABLE fixture_serial_fixture (id '.$serialType.' PRIMARY KEY, name VARCHAR(255) NOT NULL)');
        $entity = new PostgreSqlIdentifierFixture();
        $em->persist($entity);
        $em->flush();
        $this->assertSame($firstId, $entity->id);
        $this->assertSame($entity->name, $this->connection->fetchOne('SELECT name FROM fixture_serial_fixture WHERE id = 1'));
        $manager = $this->connection->createSchemaManager();
        $source = $manager->introspectTable('fixture_serial_fixture');
        $this->assertTrue($source->getColumn('id')->getAutoincrement());
        $this->assertSame([], $this->connection->getDatabasePlatform()->getAlterTableSQL($manager->createComparator()->compareTables($source, $target)));
        $this->connection->insert('fixture_serial_fixture', ['name' => 'Native generated identifier']);
        $this->assertSame([1, 2], $this->connection->fetchFirstColumn('SELECT id FROM fixture_serial_fixture ORDER BY id'));
        $em->close();
    }

    public static function identifierTypes(): iterable
    {
        yield 'integer' => ['integer', 'SERIAL', 1];
        yield 'bigint' => ['bigint', 'BIGSERIAL', '1'];
    }
}

final class PostgreSqlIdentifierFixture
{
    public int|string|null $id = null;
    public string $name = 'Cold metadata fixture';
}
