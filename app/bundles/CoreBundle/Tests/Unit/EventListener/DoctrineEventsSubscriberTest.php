<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Mautic\CoreBundle\EventListener\DoctrineEventsSubscriber;
use Mautic\CoreBundle\Test\Extensions\DbPrefix\DbPrefix;
use Mautic\PageBundle\Entity\Hit;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class DoctrineEventsSubscriberTest extends TestCase
{
    #[DataProvider('providePlatforms')]
    public function testSchemaNormalizesIndexLengthsEvenWhenMetadataWasCachedOnAnotherPlatform(bool $postgres): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($postgres ? new PostgreSQLPlatform() : new MySQLPlatform());
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $schema = new Schema();
        $table  = $schema->createTable('pages');
        $table->addColumn('id', 'integer');
        $table->addColumn('url', 'text');
        $table->addColumn('slug', 'string');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['url'], 'page_url', [], ['lengths' => [128]]);
        $table->addUniqueIndex(['slug'], 'page_slug', ['lengths' => [16]]);

        (new DoctrineEventsSubscriber(''))->postGenerateSchema(new GenerateSchemaEventArgs($em, $schema));

        $this->assertSame(!$postgres, $table->getIndex('page_url')->hasOption('lengths'));
        $this->assertSame(!$postgres, $table->getIndex('page_slug')->hasOption('lengths'));
        $this->assertTrue($table->getIndex('page_slug')->isUnique());
        $this->assertSame(['id'], $table->getPrimaryKey()->getColumns());
        $this->assertSame($postgres ? 'mautic_unicode_ci' : null, $table->getColumn('url')->getPlatformOptions()['collation'] ?? null);
        $this->assertSame($postgres ? 'mautic_unicode_ci' : null, $table->getColumn('slug')->getPlatformOptions()['collation'] ?? null);
    }

    public static function providePlatforms(): iterable
    {
        yield 'PostgreSQL' => [true];
        yield 'MySQL' => [false];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testInstallerPrefixOverridesThePreviouslyConfiguredPrefix(): void
    {
        (new DbPrefix())->defineDbPrefix();
        defined('MAUTIC_INSTALLER') || define('MAUTIC_INSTALLER', 1);
        $metadata = new ClassMetadata(User::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->setPrimaryTable(['name' => 'users']);
        $subscriber = new DoctrineEventsSubscriber('previous_');

        $subscriber->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $this->createStub(EntityManagerInterface::class)));

        $this->assertSame(MAUTIC_TABLE_PREFIX.'users', $metadata->getTableName());
    }

    #[DataProvider('indexPlatforms')]
    public function testOnlySupportedIndexLengthsRemainInMetadata(AbstractPlatform $platform, bool $supportsLengths): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $metadata = new ClassMetadata(Hit::class);
        $metadata->initializeReflection(new RuntimeReflectionService());
        Hit::loadMetadata($metadata);

        (new DoctrineEventsSubscriber(MAUTIC_TABLE_PREFIX))->loadClassMetadata(new LoadClassMetadataEventArgs($metadata, $entityManager));

        $options = $metadata->table['indexes'][MAUTIC_TABLE_PREFIX.'page_hit_url']['options'];
        $this->assertSame($supportsLengths ? [128] : null, $options['lengths'] ?? null);
    }

    /**
     * @return iterable<string, array{AbstractPlatform, bool}>
     */
    public static function indexPlatforms(): iterable
    {
        yield 'MySQL keeps the prefix length' => [new MySQLPlatform(), true];
        yield 'PostgreSQL ignores unsupported prefix lengths' => [new PostgreSQLPlatform(), false];
    }
}
