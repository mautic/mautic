<?php

namespace Mautic\InstallBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\LeadBundle\Field\SchemaDefinition;
use Mautic\LeadBundle\Model\FieldModel;

#[AsDoctrineListener(ToolEvents::postGenerateSchema)]
final class DoctrineEventSubscriber
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $fieldGroups = [
            'leads'     => FieldModel::$coreFields,
            'companies' => FieldModel::$coreCompanyFields,
        ];

        foreach ($fieldGroups as $tableName => $fields) {
            $fullTableName = MAUTIC_TABLE_PREFIX.$tableName;
            if (!$args->getSchema()->hasTable($fullTableName)) {
                // Ignore during plugin installations as not all tables are present in the schema.
                continue;
            }
            $table = $args->getSchema()->getTable($fullTableName);

            foreach ($fields as $alias => $field) {
                if (!$table->hasColumn($alias)) {
                    $type       = $field['type'] ?? 'text';
                    $definition = SchemaDefinition::getSchemaDefinition($alias, $type, !empty($field['unique']));
                    $table->addColumn($definition['name'], $definition['type'], $definition['options']);

                    if ('textarea' !== $type) {
                        $table->addIndex([$definition['name']], $definition['name'].'_search');
                    }
                }
            }

            if ('leads' === $tableName) {
                // Add an attribution index
                $table->addIndex(['attribution', 'attribution_date'], 'contact_attribution');
                // Add date added and country index
                $table->addIndex(['date_added', 'country'], 'date_added_country_index');
            } else {
                $table->addIndex(['companyname', 'companyemail'], 'company_filter');
                $table->addIndex(['companyname', 'companycity', 'companycountry', 'companystate'], 'company_match');
            }
        }

        // Add MySQL missing functions + operators to PostgreSQL
        if ($args->getEntityManager()->getConnection() instanceof Connection) {
            // only run when real connetion exists
            $this->postgreSqlMySqlCompact($args->getEntityManager()->getConnection());
        }
    }

    public function postgreSqlMySqlCompact(Connection $conn): void
    {
        if (!DatabasePlatform::isPostgreSQL($conn->getDatabasePlatform())) {
            return;
        }

        // 1. Functions
        $functions = [
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
            'CREATE OR REPLACE FUNCTION convert_tz(dt timestamp with time zone, from_tz text,to_tz text)
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
            'CREATE OR REPLACE FUNCTION ifnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',
            'CREATE OR REPLACE FUNCTION isnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',
            "CREATE OR REPLACE FUNCTION format(n numeric, decimals int DEFAULT 2) RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT to_char(n, 'FM999,999,999,999,999,990D' || repeat('0', decimals));\$\$",
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
            'CREATE OR REPLACE FUNCTION from_unixtime(ts bigint) RETURNS timestamp with time zone LANGUAGE sql IMMUTABLE AS $$SELECT to_timestamp(ts);$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp(ts timestamp with time zone) RETURNS bigint LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(EPOCH FROM ts)::bigint;$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp() RETURNS bigint LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(EPOCH FROM now())::bigint;$$',
            "CREATE OR REPLACE FUNCTION sec_to_time(secs integer) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$SELECT to_char(make_interval(secs => abs(secs)), CASE WHEN secs < 0 THEN '-FMHH24:MI:SS' ELSE 'FMHH24:MI:SS' END);\$\$",
            "CREATE OR REPLACE FUNCTION sec_to_time(secs bigint) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$SELECT to_char(make_interval(secs => abs(secs)::integer), CASE WHEN secs < 0 THEN '-FMHH24:MI:SS' ELSE 'FMHH24:MI:SS' END);\$\$",
            'CREATE OR REPLACE FUNCTION substring_index(str text, delim text, count integer) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT split_part($1, $2, $3);$$',
            'CREATE OR REPLACE FUNCTION concat_ws(sep text, VARIADIC args text[]) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT array_to_string(array_remove(args,NULL),sep);$$',
            "CREATE OR REPLACE FUNCTION group_concat(val text, sep text DEFAULT ',') RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT string_agg(val,sep);\$\$",
            'CREATE OR REPLACE FUNCTION year(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION year(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION if(cond boolean, then_val anyelement, else_val anyelement)
             RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$
               SELECT CASE WHEN cond THEN then_val ELSE else_val END;
             $$',
        ];

        foreach ($functions as $sql) {
            $conn->executeStatement($sql);
        }

        // 2. Helper functions for operators
        $helperFuncs = [
            'pg_bool_eq_int'  => 'boolean = integer',
            'pg_int_eq_bool'  => 'integer = boolean',
            'pg_bool_neq_int' => 'boolean <> integer',
            'pg_int_neq_bool' => 'integer <> boolean',
        ];

        foreach ($helperFuncs as $name => $sig) {
            $conn->executeStatement(match ($sig) {
                'boolean = integer'  => "CREATE OR REPLACE FUNCTION {$name}(boolean,integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS \$\$SELECT \$1=(\$2::boolean);\$\$",
                'integer = boolean'  => "CREATE OR REPLACE FUNCTION {$name}(integer,boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS \$\$SELECT (\$1::boolean)=\$2;\$\$",
                'boolean <> integer' => "CREATE OR REPLACE FUNCTION {$name}(boolean,integer) RETURNS boolean LANGUAGE sql IMMUTABLE AS \$\$SELECT \$1<>(\$2::boolean);\$\$",
                'integer <> boolean' => "CREATE OR REPLACE FUNCTION {$name}(integer,boolean) RETURNS boolean LANGUAGE sql IMMUTABLE AS \$\$SELECT (\$1::boolean)<>\$2;\$\$",
            });
        }

        // 3. Operators – DROP IF EXISTS + CREATE (NO CREATE OR REPLACE OPTION)
        $operators = [
            '= (boolean, integer)'  => ['left' => 'boolean', 'right' => 'integer', 'proc' => 'pg_bool_eq_int', 'comm' => '=', 'neg' => '<>'],
            '= (integer, boolean)'  => ['left' => 'integer', 'right' => 'boolean', 'proc' => 'pg_int_eq_bool', 'comm' => '=', 'neg' => '<>'],
            '<> (boolean, integer)' => ['left' => 'boolean', 'right' => 'integer', 'proc' => 'pg_bool_neq_int', 'comm' => '<>', 'neg' => '='],
            '<> (integer, boolean)' => ['left' => 'integer', 'right' => 'boolean', 'proc' => 'pg_int_neq_bool', 'comm' => '<>', 'neg' => '='],
        ];

        foreach ($operators as $signature => $def) {
            $conn->executeStatement("DROP OPERATOR IF EXISTS {$signature}");
            $conn->executeStatement("
                CREATE OPERATOR {$def['comm']} (
                    PROCEDURE = {$def['proc']},
                    LEFTARG   = {$def['left']},
                    RIGHTARG  = {$def['right']},
                    COMMUTATOR = {$def['comm']},
                    NEGATOR    = {$def['neg']}
                    )");
        }
    }
}
