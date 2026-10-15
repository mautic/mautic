<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;
use Mautic\CoreBundle\Test\EnvLoader;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\Migrations\Version020230615115326;
use Mautic\Migrations\Version20200415135706;
use Mautic\Migrations\Version20220216161028;
use Mautic\Migrations\Version20230307083702;
use Mautic\Migrations\Version20230606111852;
use Mautic\Migrations\Version20231110103625;
use Mautic\Migrations\Version20240704164714;
use Mautic\Migrations\Version20250804003400;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class HistoricalDataMigrationsTest extends TestCase
{
    private Connection $connection;
    private string $namespace;

    protected function setUp(): void
    {
        EnvLoader::load();
        if ('pdo_pgsql' !== ($_ENV['DB_DRIVER'] ?? 'pdo_mysql')) {
            $this->markTestSkipped('PostgreSQL historical data migrations.');
        }
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_pgsql', 'host' => $_ENV['DB_HOST'], 'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'], 'user' => $_ENV['DB_USER'], 'password' => $_ENV['DB_PASSWD'],
        ]);
        $this->namespace = 'data_migrations_'.bin2hex(random_bytes(6));
        $this->connection->executeStatement('CREATE SCHEMA '.$this->namespace);
        $this->connection->executeStatement('SET search_path TO '.$this->namespace);
        $this->connection->executeStatement(PostgreSqlCompatibilityFunctions::COLLATION_SQL);
    }

    protected function tearDown(): void
    {
        if (isset($this->namespace)) {
            $this->connection->executeStatement('DROP SCHEMA '.$this->namespace.' CASCADE');
            $this->connection->close();
        }
    }

    public function testJsonConversionPreservesValidJsonAndNulls(): void
    {
        $tables = [
            'message_channels' => ['properties'], 'emails' => ['headers'],
            'form_fields' => ['conditions', 'validation'], 'dynamic_content' => ['utm_tags'],
            'sms_message_stats' => ['details'], 'sync_object_mapping' => ['internal_storage'],
            'imports' => ['properties'], 'lead_event_log' => ['properties'],
            'reports' => ['settings'], 'tweet_stats' => ['response_details'],
        ];
        $json = '{"value":"\\u0000","number":12345678901234567890}';
        foreach ($tables as $table => $columns) {
            $this->connection->executeStatement('CREATE TABLE '.$table.' ('.implode(', ', array_map(static fn (string $column): string => $column.' TEXT', $columns)).')');
            $this->connection->insert($table, array_fill_keys($columns, $json));
            $this->connection->insert($table, array_fill_keys($columns, null));
        }

        $this->migrate(Version020230615115326::class);

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertSame([$json, null], $this->connection->fetchFirstColumn("SELECT {$column}::text FROM {$table} ORDER BY {$column}::text NULLS LAST"));
                $this->assertSame('json', $this->connection->fetchOne('SELECT data_type FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table AND column_name = :column', ['schema' => $this->namespace, 'table' => $table, 'column' => $column]));
            }
        }
    }

    public function testCountryMigrationUpdatesSerializedFilters(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE leads (id SERIAL PRIMARY KEY, country TEXT COLLATE mautic_unicode_ci)');
        $this->connection->executeStatement('CREATE TABLE companies (id SERIAL PRIMARY KEY, companycountry TEXT COLLATE mautic_unicode_ci)');
        $this->connection->insert('leads', ['country' => 'Swaziland']);
        $this->connection->insert('leads', ['country' => null]);
        $this->connection->insert('companies', ['companycountry' => 'Swaziland']);
        $this->connection->insert('companies', ['companycountry' => null]);
        $original = serialize([['filter' => 'Swaziland'], ['filter' => 'Other']]);
        foreach (['dynamic_content' => 'filters', 'lead_lists' => 'filters', 'emails' => 'dynamic_content'] as $table => $column) {
            $this->connection->executeStatement("CREATE TABLE {$table} (id SERIAL PRIMARY KEY, {$column} TEXT COLLATE mautic_unicode_ci)");
            $this->connection->insert($table, [$column => $original]);
            $this->connection->insert($table, [$column => null]);
        }

        $this->migrate(Version20230307083702::class);

        $this->assertSame(['Eswatini', null], $this->connection->fetchFirstColumn('SELECT country FROM leads ORDER BY id'));
        $this->assertSame(['Eswatini', null], $this->connection->fetchFirstColumn('SELECT companycountry FROM companies ORDER BY id'));
        foreach (['dynamic_content' => 'filters', 'lead_lists' => 'filters', 'emails' => 'dynamic_content'] as $table => $column) {
            $this->assertNull($this->connection->fetchOne("SELECT {$column} FROM {$table} WHERE id = 2"));
            $this->assertSame([['filter' => 'Eswatini'], ['filter' => 'Other']], \Mautic\CoreBundle\Helper\Serializer::decode($this->connection->fetchOne("SELECT {$column} FROM {$table} WHERE id = 1")));
        }
    }

    public function testCompanyFieldMigrationMatchesAndPreservesNullsOnIcuText(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE form_fields (id SERIAL PRIMARY KEY, lead_field TEXT COLLATE mautic_unicode_ci)');
        foreach (['company_name', 'company', 'email', null] as $field) {
            $this->connection->insert('form_fields', ['lead_field' => $field]);
        }

        $this->migrate(Version20200415135706::class);

        $this->assertSame(
            [['company_name', 'company', 'company_name'], ['company', 'contact', 'company'], ['email', 'contact', 'email'], [null, null, null]],
            $this->connection->fetchAllNumeric('SELECT lead_field, mapped_object, mapped_field FROM form_fields ORDER BY id')
        );
    }

    public function testRegionMigrationReplacesSerializedValuesOnIcuText(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE leads (id SERIAL PRIMARY KEY, state TEXT COLLATE mautic_unicode_ci)');
        $this->connection->executeStatement('CREATE TABLE companies (id SERIAL PRIMARY KEY, companystate TEXT COLLATE mautic_unicode_ci)');
        $this->connection->insert('leads', ['state' => "Val d'Oise"]);
        $this->connection->insert('leads', ['state' => null]);
        $this->connection->insert('companies', ['companystate' => 'Réunion']);
        $this->connection->insert('companies', ['companystate' => null]);

        $filters = [serialize(['filter' => "Val d'Oise"]), serialize(['filter' => 'Réunion'])];
        foreach (['dynamic_content' => 'filters', 'lead_lists' => 'filters', 'emails' => 'dynamic_content'] as $table => $column) {
            $this->connection->executeStatement("CREATE TABLE {$table} (id SERIAL PRIMARY KEY, {$column} TEXT COLLATE mautic_unicode_ci)");
            $this->connection->insert($table, [$column => $filters[0]]);
            $this->connection->insert($table, [$column => $filters[1]]);
            $this->connection->insert($table, [$column => null]);
        }

        $this->migrate(Version20220216161028::class);

        $this->assertSame(["Val-d'Oise", null], $this->connection->fetchFirstColumn('SELECT state FROM leads ORDER BY id'));
        $this->assertSame(['La Réunion', null], $this->connection->fetchFirstColumn('SELECT companystate FROM companies ORDER BY id'));
        $expected = [serialize(['filter' => "Val-d'Oise"]), serialize(['filter' => 'La Réunion']), null];
        foreach (['dynamic_content' => 'filters', 'lead_lists' => 'filters', 'emails' => 'dynamic_content'] as $table => $column) {
            $this->assertSame($expected, $this->connection->fetchFirstColumn("SELECT {$column} FROM {$table} ORDER BY id"));
        }
    }

    public function testCampaignDescriptionMigrationPreservesSerializedPropertiesAndNullsOnIcuText(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE campaign_events (id SERIAL PRIMARY KEY, properties TEXT COLLATE mautic_unicode_ci)');
        $original = serialize(['settings' => ['description' => Version20230606111852::OLD_STRING, 'other' => 'preserved']]);
        $this->connection->insert('campaign_events', ['properties' => $original]);
        $this->connection->insert('campaign_events', ['properties' => null]);

        $this->migrate(Version20230606111852::class);

        $properties = $this->connection->fetchFirstColumn('SELECT properties FROM campaign_events ORDER BY id');
        $this->assertNull($properties[1]);
        $decoded = \Mautic\CoreBundle\Helper\Serializer::decode($properties[0]);
        $this->assertSame(Version20230606111852::NEW_STRING, $decoded['settings']['description']);
        $this->assertSame('preserved', $decoded['settings']['other']);
    }

    public function testHtmlFieldMigrationPreservesUnmatchedAndNullPropertiesOnIcuText(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE lead_fields (id SERIAL PRIMARY KEY, type TEXT COLLATE mautic_unicode_ci, properties TEXT COLLATE mautic_unicode_ci)');
        $this->connection->insert('lead_fields', ['type' => 'textarea', 'properties' => 'a:1:{s:9:"allowHtml";s:1:"1";}']);
        $this->connection->insert('lead_fields', ['type' => 'textarea', 'properties' => null]);
        $this->connection->insert('lead_fields', ['type' => 'textarea', 'properties' => 'a:0:{}']);

        $this->migrate(Version20240704164714::class);

        $this->assertSame(
            [['html', 'a:0:{}'], ['textarea', null], ['textarea', 'a:0:{}']],
            $this->connection->fetchAllNumeric('SELECT type, properties FROM lead_fields ORDER BY id')
        );
    }

    public function testMultiselectMigrationFindsIcuSerializedFiltersAndPreservesNulls(): void
    {
        $this->installCompatibilityFunctions();
        $this->connection->executeStatement('CREATE TABLE lead_lists (id SERIAL PRIMARY KEY, filters TEXT COLLATE mautic_unicode_ci)');
        $filters = serialize([['type' => 'multiselect', 'operator' => OperatorOptions::INCLUDING_ANY, 'field' => 'tags', 'filter' => ['a', 'b']]]);
        $this->connection->insert('lead_lists', ['filters' => $filters]);
        $this->connection->insert('lead_lists', ['filters' => null]);

        $this->migrate(Version20250804003400::class);

        $this->assertNull($this->connection->fetchOne('SELECT filters FROM lead_lists WHERE id = 2'));
        $updated = \Mautic\CoreBundle\Helper\Serializer::decode($this->connection->fetchOne('SELECT filters FROM lead_lists WHERE id = 1'));
        $this->assertSame(OperatorOptions::INCLUDING_ALL, $updated[0]['operator']);
        $this->assertSame(['a', 'b'], $updated[0]['filter']);
    }

    private function installCompatibilityFunctions(): void
    {
        foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
            $this->connection->executeStatement($sql);
        }
    }

    public function testExportPermissionsHandleSerializedNullWithoutLosingExistingPermissions(): void
    {
        $this->connection->executeStatement('CREATE TABLE roles (id INTEGER PRIMARY KEY, is_admin BOOLEAN, readable_permissions TEXT)');
        $this->connection->executeStatement('CREATE TABLE permissions (id SERIAL PRIMARY KEY, role_id INTEGER, bundle TEXT, name TEXT, bitwise INTEGER, UNIQUE (role_id, bundle, name))');
        $existing = ['lead:leads' => ['viewown']];
        $this->connection->insert('roles', ['id' => 1, 'is_admin' => 'false', 'readable_permissions' => 'N;']);
        $this->connection->insert('roles', ['id' => 2, 'is_admin' => 'false', 'readable_permissions' => serialize($existing)]);
        $this->connection->insert('roles', ['id' => 3, 'is_admin' => 'true', 'readable_permissions' => 'N;']);

        $this->migrate(Version20231110103625::class);
        $this->migrate(Version20231110103625::class);

        $this->assertSame(6, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM permissions'));
        foreach ([1, 2] as $roleId) {
            $permissions = \Mautic\CoreBundle\Helper\Serializer::decode($this->connection->fetchOne('SELECT readable_permissions FROM roles WHERE id = ?', [$roleId]));
            $this->assertSame(['enable'], $permissions['lead:export']);
            $this->assertSame(['enable'], $permissions['form:export']);
            $this->assertSame(['enable'], $permissions['report:export']);
            if (2 === $roleId) {
                $this->assertSame($existing['lead:leads'], $permissions['lead:leads']);
            }
        }
        $this->assertSame('N;', $this->connection->fetchOne('SELECT readable_permissions FROM roles WHERE id = 3'));
    }

    /**
     * @param class-string<AbstractMauticMigration> $class
     */
    private function migrate(string $class): void
    {
        $migration = new $class($this->connection, new NullLogger());
        $migration->setPrefix('');
        $migration->up(new Schema());
        foreach ($migration->getSql() as $query) {
            $this->connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
