<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Mautic\CampaignBundle\EventListener\GeneratedColumnSubscriber as CampaignSubscriber;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use Mautic\CoreBundle\Doctrine\Type\GeneratedType;
use Mautic\CoreBundle\EventListener\DoctrineGeneratedColumnsListener;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\EmailBundle\EventListener\GeneratedColumnSubscriber as EmailSubscriber;
use Mautic\LeadBundle\EventListener\GeneratedColumnSubscriber as LeadSubscriber;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PostgreSqlGeneratedColumnsTest extends TestCase
{
    private Connection $connection;

    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL native stored generated columns.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql', 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'generated_columns_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        if (!Type::hasType(GeneratedType::GENERATED)) {
            Type::addType(GeneratedType::GENERATED, GeneratedType::class);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    public function testNativeGeneratedColumnsRecomputeAndRoundTripThroughSchemaIntrospection(): void
    {
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
        $versionProvider = new VersionProvider($this->connection);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new CampaignSubscriber($versionProvider));
        $dispatcher->addSubscriber(new EmailSubscriber($this->connection));
        $dispatcher->addSubscriber(new LeadSubscriber($this->createStub(ListModel::class), $this->createStub(TranslatorInterface::class), $this->connection));
        $provider = new GeneratedColumnsProvider($versionProvider, $dispatcher);
        $schema = new Schema();
        $schema->createTable(MAUTIC_TABLE_PREFIX.'leads')->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
        $emailStats = $schema->createTable(MAUTIC_TABLE_PREFIX.'email_stats');
        $emailStats->addColumn('date_sent', 'datetime', ['notnull' => false]);
        $emailStats->addColumn('email_id', 'integer', ['notnull' => false]);
        $campaignLeads = $schema->createTable(MAUTIC_TABLE_PREFIX.'campaign_leads');
        $campaignLeads->addColumn('date_added', 'datetime', ['notnull' => false]);
        $campaignLeads->addColumn('campaign_id', 'integer', ['notnull' => false]);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->connection);
        (new DoctrineGeneratedColumnsListener($provider, new NullLogger()))->postGenerateSchema(new GenerateSchemaEventArgs($entityManager, $schema));
        $this->assertCount(7, $provider->getGeneratedColumns());
        $platform = $this->connection->getDatabasePlatform();
        foreach ($schema->toSql($platform) as $query) {
            $this->connection->executeStatement($query);
        }

        foreach (['local@example.com', 'no-at-sign', null] as $email) {
            $this->connection->insert(MAUTIC_TABLE_PREFIX.'leads', ['email' => $email]);
        }
        $this->assertSame(['example.com', 'no-at-sign', null], $this->connection->fetchFirstColumn('SELECT generated_email_domain FROM '.MAUTIC_TABLE_PREFIX.'leads ORDER BY email NULLS LAST'));
        $this->connection->update(MAUTIC_TABLE_PREFIX.'leads', ['email' => 'changed@updated.example'], ['email' => 'local@example.com']);
        $this->assertSame('updated.example', $this->connection->fetchOne('SELECT generated_email_domain FROM '.MAUTIC_TABLE_PREFIX."leads WHERE email = 'changed@updated.example'"));
        $this->connection->insert(MAUTIC_TABLE_PREFIX.'email_stats', ['date_sent' => '2024-01-01 12:34:56']);
        $this->assertSame('2024-01-01', $this->connection->fetchOne('SELECT generated_sent_date FROM '.MAUTIC_TABLE_PREFIX.'email_stats'));

        foreach (['2023-01-01 12:34:56', '2023-12-31 12:34:56', '2024-01-01 12:34:56', '2024-01-07 12:34:56', null] as $date) {
            $this->connection->insert(MAUTIC_TABLE_PREFIX.'campaign_leads', ['date_added' => $date]);
        }
        $this->assertSame(['2023 01', '2023 53', '2024 00', '2024 01', null], $this->connection->fetchFirstColumn('SELECT generated_date_added_week FROM '.MAUTIC_TABLE_PREFIX.'campaign_leads ORDER BY date_added NULLS LAST'));
        $this->assertSame(['2024-01-01 12:00:00', '2024-01-01', '2024-01', 2024], array_values($this->connection->fetchAssociative('SELECT generated_date_added_hour, generated_date_added_day, generated_date_added_month, generated_date_added_year FROM '.MAUTIC_TABLE_PREFIX."campaign_leads WHERE date_added = '2024-01-01 12:34:56'")));
        $this->connection->update(MAUTIC_TABLE_PREFIX.'campaign_leads', ['date_added' => '2025-02-02 01:02:03'], ['date_added' => '2024-01-01 12:34:56']);
        $this->assertSame('2025-02', $this->connection->fetchOne('SELECT generated_date_added_month FROM '.MAUTIC_TABLE_PREFIX."campaign_leads WHERE date_added = '2025-02-02 01:02:03'"));

        $prefixIndex = DatabasePlatform::getCreateIndexSql($platform, MAUTIC_TABLE_PREFIX.'leads', 'prefix_expression_index', ['email(191)', 'LOWER(email)']);
        $this->connection->executeStatement($prefixIndex);
        $this->assertStringContainsString('lower(', (string) $this->connection->fetchOne("SELECT pg_get_indexdef('prefix_expression_index'::regclass)"));
        $this->connection->executeStatement($platform->getDropIndexSQL('prefix_expression_index'));

        $manager = $this->connection->createSchemaManager();
        foreach ($provider->getGeneratedColumns() as $column) {
            $table = $manager->introspectTable($column->getTableName());
            $this->assertInstanceOf(GeneratedType::class, $table->getColumn($column->getColumnName())->getType());
            $this->assertNull($table->getColumn($column->getColumnName())->getDefault());
            $this->assertTrue($table->hasIndex($column->getIndexName()));
            $diff = $manager->createComparator()->compareTables($table, $schema->getTable($column->getTableName()));
            $this->assertSame([], $platform->getAlterTableSQL($diff));
        }
    }
}
