<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

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
        $this->abortIf(
            !DatabasePlatform::isPostgreSQL($platform),
            'Migration can only be executed safely on \'postgresql\'.'
        );

        // === 1. Create / replace functions ===
        $functions = [
            // date_format emulation
            "CREATE OR REPLACE FUNCTION date_format(tz timestamp with time zone, fmt text)
             RETURNS text LANGUAGE plpgsql IMMUTABLE AS \$\$
             DECLARE
                 f text := fmt;
             BEGIN
                 f := REPLACE(f, '%T', 'HH24:MI:SS');
                 f := REPLACE(f, '%Y', 'YYYY');
                 f := REPLACE(f, '%y', 'YY');
                 f := REPLACE(f, '%m', 'MM');
                 f := REPLACE(f, '%c', 'FMMonth');
                 f := REPLACE(f, '%M', 'Month');
                 f := REPLACE(f, '%b', 'Mon');
                 f := REPLACE(f, '%e', 'FMDD');
                 f := REPLACE(f, '%d', 'DD');
                 f := REPLACE(f, '%k', 'FMHH24');
                 f := REPLACE(f, '%l', 'FMHH12');
                 f := REPLACE(f, '%H', 'HH24');
                 f := REPLACE(f, '%h', 'HH12');
                 f := REPLACE(f, '%i', 'MI');
                 f := REPLACE(f, '%s', 'SS');
                 f := REPLACE(f, '%p', 'AM');

                 RETURN TO_CHAR(tz AT TIME ZONE 'UTC', f);
             END;
             \$\$;",

            // convert_tz
            'CREATE OR REPLACE FUNCTION convert_tz(dt timestamp with time zone, from_tz text, to_tz text)
            RETURNS timestamp with time zone LANGUAGE plpgsql IMMUTABLE AS $$
            DECLARE
                result timestamptz;
            BEGIN
                IF dt IS NULL THEN
                    RETURN NULL;
                END IF;
                BEGIN
                    result := dt AT TIME ZONE from_tz AT TIME ZONE to_tz;
                EXCEPTION WHEN invalid_parameter_value THEN
                    RETURN NULL;
                END;
                RETURN result;
            END;
            $$',

            // ifnull / isnull
            'CREATE OR REPLACE FUNCTION ifnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',
            'CREATE OR REPLACE FUNCTION isnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',

            // format (numeric)
            "CREATE OR REPLACE FUNCTION format(n numeric, decimals int DEFAULT 2) RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT to_char(n, 'FM999,999,999,999,999,990D' || repeat('0', decimals));\$\$",

            // find_in_set
            "CREATE OR REPLACE FUNCTION find_in_set(needle text, haystack text)
             RETURNS integer LANGUAGE plpgsql IMMUTABLE AS \$\$
             DECLARE
                 arr text[];
                 i integer := 1;
                 elem text;
             BEGIN
                 IF haystack IS NULL OR needle IS NULL THEN
                     RETURN 0;
                 END IF;
                 arr := string_to_array(haystack, ',');
                 FOREACH elem IN ARRAY arr LOOP
                     IF TRIM(elem) = needle THEN
                         RETURN i;
                     END IF;
                     i := i + 1;
                 END LOOP;
                 RETURN 0;
             END;
             \$\$;",

            // from_unixtime / unix_timestamp
            'CREATE OR REPLACE FUNCTION from_unixtime(ts bigint) RETURNS timestamp with time zone LANGUAGE sql IMMUTABLE AS $$SELECT to_timestamp(ts);$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp(ts timestamp with time zone) RETURNS bigint LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(EPOCH FROM ts)::bigint;$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp() RETURNS bigint LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(EPOCH FROM now())::bigint;$$',

            // sec_to_time (two overloads)
            "CREATE OR REPLACE FUNCTION sec_to_time(secs integer) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$SELECT to_char(make_interval(secs => abs(secs)), CASE WHEN secs < 0 THEN '-FMHH24:MI:SS' ELSE 'FMHH24:MI:SS' END);\$\$",
            "CREATE OR REPLACE FUNCTION sec_to_time(secs bigint) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$SELECT to_char(make_interval(secs => abs(secs)::integer), CASE WHEN secs < 0 THEN '-FMHH24:MI:SS' ELSE 'FMHH24:MI:SS' END);\$\$",

            // substring_index, concat_ws, group_concat
            'CREATE OR REPLACE FUNCTION substring_index(str text, delim text, count integer) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT split_part($1, $2, $3);$$',
            'CREATE OR REPLACE FUNCTION concat_ws(sep text, VARIADIC args text[]) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT array_to_string(array_remove(args,NULL),sep);$$',
            "CREATE OR REPLACE FUNCTION group_concat(val text, sep text DEFAULT ',') RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT string_agg(val,sep);\$\$",

            // year/month/day overloads
            'CREATE OR REPLACE FUNCTION year(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION year(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',

            'CREATE OR REPLACE FUNCTION lower(data jsonb) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT lower(data::text);$$',

            // if (CASE emulation)
            'CREATE OR REPLACE FUNCTION if(cond boolean, then_val anyelement, else_val anyelement)
             RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$
               SELECT CASE WHEN cond THEN then_val ELSE else_val END;
             $$',
        ];

        foreach ($functions as $sql) {
            $this->addSql($sql);
        }

        // === 2. Helper functions for bool ↔ int comparisons ===
        $helperFuncs = [
            'pg_bool_eq_int'  => 'CREATE OR REPLACE FUNCTION pg_bool_eq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1 = ($2::boolean);$$',
            'pg_int_eq_bool'  => 'CREATE OR REPLACE FUNCTION pg_int_eq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT ($1::boolean) = $2;$$',
            'pg_bool_neq_int' => 'CREATE OR REPLACE FUNCTION pg_bool_neq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT $1 <> ($2::boolean);$$',
            'pg_int_neq_bool' => 'CREATE OR REPLACE FUNCTION pg_int_neq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS $$SELECT ($1::boolean) <> $2;$$',
        ];

        foreach ($helperFuncs as $sql) {
            $this->addSql($sql);
        }

        // === 3. Custom operators (DROP first, then CREATE - PostgreSQL does not support CREATE OR REPLACE OPERATOR) ===
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

        $this->abortIf(
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
