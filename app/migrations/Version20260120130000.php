<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\PostgreSqlCompatibilityFunctions;

final class Version20260120130000 extends AbstractMauticMigration
{
    public function getDescription(): string
    {
        return 'Adds PostgreSQL functions and operators for MySQL compatibility';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        // Safety check - only run on PostgreSQL
        $this->skipIf(
            !DatabasePlatform::isPostgreSQL($platform),
            'Migration can only be executed safely on \'postgresql\'.'
        );

        // Create / replace functions and operator helpers.
        foreach (PostgreSqlCompatibilityFunctions::getSql() as $sql) {
            $this->addSql($sql);
        }

        // Custom operators (PostgreSQL does not support CREATE OR REPLACE OPERATOR).
        $operators = [
            // = (boolean, integer)
            'DROP OPERATOR IF EXISTS = (boolean, integer);',
            'CREATE OPERATOR = (
                PROCEDURE = pg_bool_eq_int,
                LEFTARG   = boolean,
                RIGHTARG  = integer,
                COMMUTATOR = =,
                NEGATOR    = <>
            );',

            // = (integer, boolean)
            'DROP OPERATOR IF EXISTS = (integer, boolean);',
            'CREATE OPERATOR = (
                PROCEDURE = pg_int_eq_bool,
                LEFTARG   = integer,
                RIGHTARG  = boolean,
                COMMUTATOR = =,
                NEGATOR    = <>
            );',

            // <> (boolean, integer)
            'DROP OPERATOR IF EXISTS <> (boolean, integer);',
            'CREATE OPERATOR <> (
                PROCEDURE = pg_bool_neq_int,
                LEFTARG   = boolean,
                RIGHTARG  = integer,
                COMMUTATOR = <>,
                NEGATOR    = =
            );',

            // <> (integer, boolean)
            'DROP OPERATOR IF EXISTS <> (integer, boolean);',
            'CREATE OPERATOR <> (
                PROCEDURE = pg_int_neq_bool,
                LEFTARG   = integer,
                RIGHTARG  = boolean,
                COMMUTATOR = <>,
                NEGATOR    = =
            );',
        ];

        foreach ($operators as $sql) {
            $this->addSql($sql);
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        $this->skipIf(
            !DatabasePlatform::isPostgreSQL($platform),
            'Migration can only be executed safely on \'postgresql\'.'
        );

        // Drop operators first (reverse order)
        $this->addSql('DROP OPERATOR IF EXISTS <> (integer, boolean);');
        $this->addSql('DROP OPERATOR IF EXISTS <> (boolean, integer);');
        $this->addSql('DROP OPERATOR IF EXISTS = (integer, boolean);');
        $this->addSql('DROP OPERATOR IF EXISTS = (boolean, integer);');

        // Drop helper functions
        $this->addSql('DROP FUNCTION IF EXISTS pg_int_neq_bool(integer, boolean);');
        $this->addSql('DROP FUNCTION IF EXISTS pg_bool_neq_int(boolean, integer);');
        $this->addSql('DROP FUNCTION IF EXISTS pg_int_eq_bool(integer, boolean);');
        $this->addSql('DROP FUNCTION IF EXISTS pg_bool_eq_int(boolean, integer);');

        // Drop main functions (in reverse creation order is safer)
        $this->addSql('DROP FUNCTION IF EXISTS lower(jsonb);');
        $this->addSql('DROP FUNCTION IF EXISTS lower(json);');
        $this->addSql('DROP FUNCTION IF EXISTS day(timestamp with time zone);');
        $this->addSql('DROP FUNCTION IF EXISTS day(date);');
        $this->addSql('DROP FUNCTION IF EXISTS month(timestamp with time zone);');
        $this->addSql('DROP FUNCTION IF EXISTS month(date);');
        $this->addSql('DROP FUNCTION IF EXISTS year(timestamp with time zone);');
        $this->addSql('DROP FUNCTION IF EXISTS year(date);');
        $this->addSql('DROP FUNCTION IF EXISTS group_concat(text, text);');
        $this->addSql('DROP FUNCTION IF EXISTS concat_ws(text, text[]);');
        $this->addSql('DROP FUNCTION IF EXISTS substring_index(text, text, integer);');
        $this->addSql('DROP FUNCTION IF EXISTS sec_to_time(bigint);');
        $this->addSql('DROP FUNCTION IF EXISTS sec_to_time(integer);');
        $this->addSql('DROP FUNCTION IF EXISTS unix_timestamp();');
        $this->addSql('DROP FUNCTION IF EXISTS unix_timestamp(timestamp with time zone);');
        $this->addSql('DROP FUNCTION IF EXISTS from_unixtime(bigint);');
        $this->addSql('DROP FUNCTION IF EXISTS if(boolean, anyelement, anyelement);');
        $this->addSql('DROP FUNCTION IF EXISTS find_in_set(text, text);');
        $this->addSql('DROP FUNCTION IF EXISTS format(numeric, int);');
        $this->addSql('DROP FUNCTION IF EXISTS isnull(anyelement, anyelement);');
        $this->addSql('DROP FUNCTION IF EXISTS ifnull(anyelement, anyelement);');
        $this->addSql('DROP FUNCTION IF EXISTS convert_tz(timestamp with time zone, text, text);');
        $this->addSql('DROP FUNCTION IF EXISTS date_format(timestamp with time zone, text);');
    }
}
