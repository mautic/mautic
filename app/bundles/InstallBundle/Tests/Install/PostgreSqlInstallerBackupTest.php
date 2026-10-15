<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Install;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\InstallBundle\Helper\SchemaHelper;
use PHPUnit\Framework\TestCase;

final class PostgreSqlInstallerBackupTest extends TestCase
{
    private Connection $connection;
    private string $namespace;
    private array $params;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL installer backup lifecycle.');
        }
        $this->params = [
            'driver' => $_ENV['DB_DRIVER'], 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
            'table_prefix' => 'fixture_', 'backup_prefix' => 'bak_',
        ];
        $this->connection = DriverManager::getConnection($this->params);
        $this->namespace = 'installer_backup_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    public function testReinstallPreservesFormValuesConstraintsGeneratedColumnsAndOtherPrefixes(): void
    {
        $this->install(false);
        $this->populate('original@example.com');
        $other = new Table('other_form_results_1_fixture_signup');
        $other->addColumn('value', 'string');
        $this->connection->createSchemaManager()->createTable($other);
        $this->connection->insert($other->getName(), ['value' => 'unrelated']);

        $this->install(true);
        $this->assertBackup('original@example.com');
        $this->connection->insert('bak_forms', ['email' => 'SECOND@EXAMPLE.COM']);
        $this->assertSame(2, (int) $this->connection->fetchOne("SELECT id FROM bak_forms WHERE email = 'SECOND@EXAMPLE.COM'"));
        $this->assertSame('second@example.com', $this->connection->fetchOne('SELECT normalized_email FROM bak_forms WHERE id = 2'));
        $this->populate('replacement@example.com');

        // A second backup replaces the previous backup, including its dynamic tables and indexes.
        $this->install(true);
        $this->assertBackup('replacement@example.com');
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM fixture_forms'));
        $this->populate('discarded@example.com');
        $this->install(false);
        $this->assertFalse($this->connection->createSchemaManager()->tablesExist(['fixture_form_results_1_fixture_signup']));
        $this->assertSame('replacement@example.com', $this->connection->fetchOne('SELECT email FROM bak_form_results_1_fixture_signup'));
        $this->assertSame('unrelated', $this->connection->fetchOne('SELECT value FROM other_form_results_1_fixture_signup'));
        $this->connection->insert('fixture_forms', ['email' => 'fresh@example.com']);
        $this->assertSame(1, (int) $this->connection->fetchOne('SELECT id FROM fixture_forms'));

        // A stale result backup is removed even when the replacement has no matching runtime table.
        $this->install(true);
        $this->assertFalse($this->connection->createSchemaManager()->tablesExist(['bak_form_results_1_fixture_signup']));
        $this->assertSame('fresh@example.com', $this->connection->fetchOne('SELECT email FROM bak_forms'));
        $this->assertSame('unrelated', $this->connection->fetchOne('SELECT value FROM other_form_results_1_fixture_signup'));
    }

    private function populate(string $email): void
    {
        $this->connection->insert('fixture_forms', ['email' => $email]);
        $this->connection->insert('fixture_form_submissions', ['form_id' => 1]);
        $results = new Table('fixture_form_results_1_fixture_signup');
        $results->addColumn('submission_id', 'integer');
        $results->addColumn('email', 'string');
        $results->setPrimaryKey(['submission_id']);
        $results->addIndex(['email'], 'fixture_result_email');
        $results->addForeignKeyConstraint('fixture_form_submissions', ['submission_id'], ['id'], ['onDelete' => 'CASCADE']);
        $this->connection->createSchemaManager()->createTable($results);
        $this->connection->insert($results->getName(), ['submission_id' => 1, 'email' => $email]);
    }

    private function assertBackup(string $email): void
    {
        $this->assertSame($email, $this->connection->fetchOne('SELECT email FROM bak_forms'));
        $this->assertSame($email, $this->connection->fetchOne('SELECT email FROM bak_form_results_1_fixture_signup'));
        $manager = $this->connection->createSchemaManager();
        $this->assertCount(1, $manager->listTableForeignKeys('bak_form_submissions'));
        $this->assertCount(1, $manager->listTableForeignKeys('bak_form_results_1_fixture_signup'));
        $this->assertArrayHasKey('bak_result_email', $manager->listTableIndexes('bak_form_results_1_fixture_signup'));
        $this->assertArrayHasKey('bak_normalized_fixture_email', $manager->listTableIndexes('bak_forms'));
        $this->connection->executeStatement("UPDATE bak_forms SET email = 'CHANGED@EXAMPLE.COM'");
        $this->assertSame('changed@example.com', $this->connection->fetchOne('SELECT normalized_email FROM bak_forms'));
        $this->connection->delete('bak_form_submissions', ['id' => 1]);
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM bak_form_results_1_fixture_signup'));
        $this->connection->insert('bak_form_submissions', ['id' => 1, 'form_id' => 1]);
        $this->connection->insert('bak_form_results_1_fixture_signup', ['submission_id' => 1, 'email' => $email]);
    }

    private function install(bool $backup): void
    {
        $helper = new SchemaHelper($this->params + ['backup_tables' => $backup]);
        $db = (new \ReflectionProperty(SchemaHelper::class, 'db'))->getValue($helper);
        $db->executeStatement('SET search_path TO '.$this->namespace);
        $forms = new ClassMetadata(\stdClass::class);
        $forms->setPrimaryTable(['name' => 'fixture_forms']);
        $forms->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $forms->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $forms->mapField(['fieldName' => 'email', 'type' => 'string']);
        $submissions = new ClassMetadata(\ArrayObject::class);
        $submissions->setPrimaryTable(['name' => 'fixture_form_submissions']);
        $submissions->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $submissions->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
        $submissions->mapField(['fieldName' => 'form_id', 'type' => 'integer']);
        $factory = $this->createStub(ClassMetadataFactory::class);
        $factory->method('getAllMetadata')->willReturn([$forms, $submissions]);
        $events = new EventManager();
        $events->addEventListener(ToolEvents::postGenerateSchema, new class() {
            public function postGenerateSchema(GenerateSchemaEventArgs $event): void
            {
                $table = $event->getSchema()->getTable('fixture_forms');
                $table->addColumn('normalized_email', 'string', ['columnDefinition' => 'VARCHAR(255) GENERATED ALWAYS AS (LOWER(email)) STORED']);
                $table->addIndex(['normalized_email'], 'fixture_normalized_fixture_email');
                $event->getSchema()->getTable('fixture_form_submissions')->addForeignKeyConstraint('fixture_forms', ['form_id'], ['id']);
            }
        });
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($db);
        $em->method('getConfiguration')->willReturn(new Configuration());
        $em->method('getMetadataFactory')->willReturn($factory);
        $em->method('getEventManager')->willReturn($events);
        $helper->setEntityManager($em);
        $this->assertTrue($helper->installSchema());
    }
}
