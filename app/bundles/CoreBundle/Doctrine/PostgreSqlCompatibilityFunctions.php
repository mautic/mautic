<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

final class PostgreSqlCompatibilityFunctions
{
    public const COLLATION_SQL = 'CREATE COLLATION IF NOT EXISTS mautic_unicode_ci (provider = icu, locale = \'und-u-ks-level1\', deterministic = false)';

    /**
     * @return list<string>
     */
    public static function getSql(): array
    {
        return [
            self::COLLATION_SQL,
            // date_format emulation
            "CREATE OR REPLACE FUNCTION date_format(tz timestamp with time zone, fmt text)
             RETURNS text LANGUAGE plpgsql IMMUTABLE AS \$\$
             DECLARE
                 f text COLLATE \"C\" := fmt;
             BEGIN
                 f := REPLACE(f, '%T', 'HH24:MI:SS');
                 f := REPLACE(f, '%Y', 'YYYY');
                 f := REPLACE(f, '%y', 'YY');
                 f := REPLACE(f, '%m', 'MM');
                 f := REPLACE(f, '%c', 'FMMM');
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

            // Mautic stores UTC timestamps; the result carries the converted wall clock in UTC.
            "CREATE OR REPLACE FUNCTION convert_tz(dt timestamp with time zone, from_tz text, to_tz text)
             RETURNS timestamp with time zone LANGUAGE plpgsql STABLE STRICT AS \$\$
             DECLARE
                 result timestamp;
                 local_time timestamp;
                 instant timestamp with time zone;
                 earlier timestamp with time zone;
                 lower_bound timestamp with time zone;
                 upper_bound timestamp with time zone;
                 midpoint timestamp with time zone;
             BEGIN
                 IF from_tz COLLATE \"C\" ~ '^[+-][0-9]{2}:[0-9]{2}$' THEN
                     result := (dt AT TIME ZONE 'UTC') - from_tz::interval;
                 ELSIF from_tz = 'UTC' THEN
                     result := dt AT TIME ZONE 'UTC';
                 ELSE
                     local_time := dt AT TIME ZONE 'UTC';
                     instant := local_time AT TIME ZONE from_tz;
                     -- MySQL chooses the first occurrence of an ambiguous local time.
                     earlier := (local_time - (((instant - interval '2 days') AT TIME ZONE from_tz)
                         - ((instant - interval '2 days') AT TIME ZONE 'UTC'))) AT TIME ZONE 'UTC';
                     IF earlier < instant AND earlier AT TIME ZONE from_tz = local_time THEN
                         instant := earlier;
                     END IF;
                     -- MySQL clamps nonexistent local times to the start of the gap.
                     -- Search the transition itself, including half-hour and missing-day changes.
                     IF instant AT TIME ZONE from_tz > local_time THEN
                         lower_bound := date_trunc('second', instant - interval '2 days');
                         upper_bound := date_trunc('second', instant);
                         WHILE upper_bound - lower_bound > interval '1 second' LOOP
                             midpoint := date_trunc('second', lower_bound + (upper_bound - lower_bound) / 2);
                             IF midpoint AT TIME ZONE from_tz < local_time THEN
                                 lower_bound := midpoint;
                             ELSE
                                 upper_bound := midpoint;
                             END IF;
                         END LOOP;
                         instant := date_trunc('second', upper_bound)
                             + (local_time - date_trunc('second', local_time));
                     END IF;
                     result := instant AT TIME ZONE 'UTC';
                 END IF;
                 IF to_tz COLLATE \"C\" ~ '^[+-][0-9]{2}:[0-9]{2}$' THEN
                     result := result + to_tz::interval;
                 ELSE
                     result := (result AT TIME ZONE 'UTC') AT TIME ZONE to_tz;
                 END IF;
                 RETURN result AT TIME ZONE 'UTC';
             EXCEPTION WHEN invalid_parameter_value OR invalid_datetime_format THEN
                 RETURN NULL;
             END;
             \$\$;",

            // ifnull / isnull
            'CREATE OR REPLACE FUNCTION ifnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',
            'CREATE OR REPLACE FUNCTION isnull(a anyelement, b anyelement) RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$SELECT coalesce($1,$2);$$',

            // format (numeric)
            "CREATE OR REPLACE FUNCTION format(n numeric, decimals int DEFAULT 2) RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT to_char(n, 'FM999,999,999,999,999,990D' || repeat('0', decimals));\$\$",

            // FIND_IN_SET compares complete list elements, preserving whitespace and NULL.
            "CREATE OR REPLACE FUNCTION find_in_set(needle text, haystack text)
             RETURNS integer LANGUAGE sql IMMUTABLE STRICT AS \$\$
                 SELECT coalesce(array_position(string_to_array(haystack COLLATE \"C\", ',') COLLATE mautic_unicode_ci, needle COLLATE mautic_unicode_ci), 0);
             \$\$;",

            // from_unixtime / unix_timestamp
            'CREATE OR REPLACE FUNCTION from_unixtime(ts bigint) RETURNS timestamp with time zone LANGUAGE sql IMMUTABLE AS $$SELECT to_timestamp(ts);$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp(ts timestamp with time zone) RETURNS bigint LANGUAGE sql IMMUTABLE AS $$SELECT floor(EXTRACT(EPOCH FROM ts))::bigint;$$',
            'CREATE OR REPLACE FUNCTION unix_timestamp() RETURNS bigint LANGUAGE sql STABLE AS $$SELECT floor(EXTRACT(EPOCH FROM statement_timestamp()))::bigint;$$',

            // MySQL TIME values are bounded to 838:59:59.
            "CREATE OR REPLACE FUNCTION sec_to_time(secs integer) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$
                 SELECT CASE WHEN secs < 0 THEN '-' ELSE '' END
                     || to_char(value / 3600, 'FM900') || ':'
                     || to_char(value / 60 % 60, 'FM00') || ':' || to_char(value % 60, 'FM00')
                 FROM (SELECT least(abs(secs::numeric), 3020399)::bigint AS value) AS duration;
             \$\$;",
            "CREATE OR REPLACE FUNCTION sec_to_time(secs bigint) RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$
                 SELECT CASE WHEN secs < 0 THEN '-' ELSE '' END
                     || to_char(value / 3600, 'FM900') || ':'
                     || to_char(value / 60 % 60, 'FM00') || ':' || to_char(value % 60, 'FM00')
                 FROM (SELECT least(abs(secs::numeric), 3020399)::bigint AS value) AS duration;
             \$\$;",

            // substring_index, concat_ws, group_concat
            "CREATE OR REPLACE FUNCTION substring_index(str text, delim text, count integer)
             RETURNS text LANGUAGE sql IMMUTABLE STRICT AS \$\$
                 SELECT CASE
                     WHEN count = 0 OR delim COLLATE \"C\" = '' THEN ''
                     WHEN count > 0 THEN array_to_string(parts[1: count], delim)
                     ELSE array_to_string(parts[greatest(cardinality(parts) + count + 1, 1): cardinality(parts)], delim)
                 END
                 FROM (SELECT string_to_array(str COLLATE \"C\", delim COLLATE \"C\") AS parts) AS value;
             \$\$;",
            'CREATE OR REPLACE FUNCTION concat_ws(sep text, VARIADIC args text[]) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT array_to_string(array_remove(args,NULL),sep);$$',
            "CREATE OR REPLACE FUNCTION group_concat(val text, sep text DEFAULT ',') RETURNS text LANGUAGE sql IMMUTABLE AS \$\$SELECT string_agg(val,sep);\$\$",

            // year/month/day overloads
            'CREATE OR REPLACE FUNCTION year(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION year(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(YEAR FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION month(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(MONTH FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(date) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',
            'CREATE OR REPLACE FUNCTION day(timestamp with time zone) RETURNS integer LANGUAGE sql IMMUTABLE AS $$SELECT EXTRACT(DAY FROM $1)::integer;$$',

            'CREATE OR REPLACE FUNCTION lower(data json) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT lower(data::text);$$',
            'CREATE OR REPLACE FUNCTION lower(data jsonb) RETURNS text LANGUAGE sql IMMUTABLE AS $$SELECT lower(data::text);$$',

            // if (CASE emulation)
            'CREATE OR REPLACE FUNCTION if(cond boolean, then_val anyelement, else_val anyelement)
             RETURNS anyelement LANGUAGE sql IMMUTABLE AS $$
               SELECT CASE WHEN cond THEN then_val ELSE else_val END;
             $$',

            // MySQL compares booleans as 0/1, preserving other integer values.
            'CREATE OR REPLACE FUNCTION pg_bool_eq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE STRICT AS $$SELECT $1::integer = $2;$$',
            'CREATE OR REPLACE FUNCTION pg_int_eq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE STRICT AS $$SELECT $1 = $2::integer;$$',
            'CREATE OR REPLACE FUNCTION pg_bool_neq_int(boolean, integer) RETURNS boolean LANGUAGE sql IMMUTABLE STRICT AS $$SELECT $1::integer <> $2;$$',
            'CREATE OR REPLACE FUNCTION pg_int_neq_bool(integer, boolean) RETURNS boolean LANGUAGE sql IMMUTABLE STRICT AS $$SELECT $1 <> $2::integer;$$',
            <<<'SQL'
            CREATE OR REPLACE FUNCTION mautic_regexp_pattern(pattern text)
            RETURNS text LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $$
            DECLARE
                result text := '';
                character text;
                following text;
                in_class boolean := false;
                class_position integer := 0;
                posix_class boolean := false;
                end_anchor text := E'(?=(?:\\r\\n|[\\n\\r\u0085\u2028\u2029])?\\Z)';
                multiline boolean := coalesce(substring(pattern COLLATE "C" FROM '^\(\?([a-z]+)\)') LIKE '%m%', false);
                dotall boolean := coalesce(substring(pattern COLLATE "C" FROM '^\(\?([a-z]+)\)') LIKE '%s%', false);
                position integer := 1;
            BEGIN
                WHILE position <= char_length(pattern) LOOP
                    character := substr(pattern, position, 1);
                    following := substr(pattern, position + 1, 1);
                    IF character = E'\\' AND following <> '' THEN
                        IF following IN ('b', 'B') THEN
                            result := result || CASE WHEN in_class THEN following ELSE E'\\' || CASE following WHEN 'b' THEN 'y' ELSE 'Y' END END;
                        ELSIF NOT in_class AND following = 'z' THEN
                            result := result || E'\\Z';
                        ELSIF NOT in_class AND following = 'Z' THEN
                            result := result || end_anchor;
                        ELSE
                            result := result || character || following;
                        END IF;
                        IF in_class THEN class_position := class_position + 1; END IF;
                        position := position + 2;
                        CONTINUE;
                    ELSIF NOT in_class AND character = '.' AND NOT dotall THEN
                        result := result || E'[^\\n\\r\u0085\u2028\u2029]';
                        position := position + 1;
                        CONTINUE;
                    ELSIF NOT in_class AND character = '$' AND NOT multiline THEN
                        result := result || end_anchor;
                        position := position + 1;
                        CONTINUE;
                    ELSIF NOT in_class AND character = '[' THEN
                        in_class := true;
                        class_position := 0;
                    ELSIF in_class THEN
                        IF posix_class THEN
                            IF character = ']' AND substr(pattern, position - 1, 1) IN (':', '.', '=') THEN
                                posix_class := false;
                            END IF;
                        ELSIF character = '[' AND following IN (':', '.', '=') THEN
                            posix_class := true;
                        ELSIF character = ']' AND class_position > 0 THEN
                            in_class := false;
                        END IF;
                        IF character <> '^' OR class_position > 0 THEN
                            class_position := class_position + 1;
                        END IF;
                    END IF;
                    result := result || character;
                    position := position + 1;
                END LOOP;
                RETURN result;
            END;
            $$;
            SQL,
            self::getLikeNormalizationSql(),
            <<<'SQL'
            CREATE OR REPLACE FUNCTION mautic_like(value text, pattern text, escape_character text DEFAULT E'\\')
            RETURNS boolean LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS $$
                SELECT mautic_unicode_ci(value COLLATE "C") COLLATE "C"
                    LIKE mautic_unicode_ci(pattern COLLATE "C", 1, escape_character COLLATE "C") COLLATE "C";
            $$;
            SQL,
            <<<'SQL'
            CREATE OR REPLACE FUNCTION mautic_like(value anyelement, pattern text, escape_character text DEFAULT E'\\')
            RETURNS boolean LANGUAGE sql STABLE STRICT PARALLEL SAFE AS $$
                SELECT mautic_like(
                    CASE WHEN pg_typeof(value) = 'boolean'::regtype
                        THEN CASE WHEN value::text = 'true' THEN '1' ELSE '0' END
                        ELSE value::text END,
                    pattern,
                    escape_character
                );
            $$;
            SQL,
        ];
    }

    private static function getLikeNormalizationSql(): string
    {
        $mapping = json_decode(file_get_contents(__DIR__.'/../Resources/unicode/utf8mb4_unicode_ci.json'), true, 512, JSON_THROW_ON_ERROR);
        $quote = static fn (string $value): string => "E'".str_replace(['\\', "'"], ['\\\\', "''"], $value)."'";
        $characters = preg_split('//u', $mapping['from'], -1, PREG_SPLIT_NO_EMPTY);
        $equivalents = preg_split('//u', $mapping['to'], -1, PREG_SPLIT_NO_EMPTY);
        $controls = '';
        $controlEquivalents = '';
        foreach ($characters as $index => $character) {
            if (ord($character[0]) < 128 && ord($character[0]) !== ord($equivalents[$index][0]) && !ctype_alpha($character) && !in_array($character, ['%', '_', '\\'], true)) {
                $controls .= $character;
                $controlEquivalents .= $equivalents[$index];
            }
        }
        $from = $quote($mapping['from']);
        $to = $quote($mapping['to']);
        $controlFrom = $quote($controls);
        $controlTo = $quote($controlEquivalents);

        return <<<SQL
            CREATE OR REPLACE FUNCTION mautic_unicode_ci(value text, pattern_mode integer DEFAULT 0, escape_character text DEFAULT E'\\\\')
            RETURNS text LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS \$mautic_like\$
            DECLARE
                result text := '';
                character text;
                escaped boolean := false;
            BEGIN
                IF pattern_mode = 0 THEN
                    IF octet_length(value) = char_length(value) THEN
                        result := translate(upper(value COLLATE "C"), E'%\\\\_', '﹪﹨︳');
                        IF result COLLATE "C" ~ E'[\\x02-\\x08\\x0e-\\x1f\\x7f]' THEN
                            result := translate(result, $controlFrom, $controlTo);
                        END IF;
                        RETURN result;
                    END IF;
                    RETURN translate(value, $from, $to);
                END IF;
                IF char_length(escape_character) > 1 THEN
                    RAISE EXCEPTION 'LIKE escape must be empty or one character' USING ERRCODE = '22025';
                END IF;
                FOREACH character IN ARRAY regexp_split_to_array(value COLLATE "C", '') LOOP
                    IF escaped THEN
                        result := result || translate(character, $from, $to);
                        escaped := false;
                    ELSIF character = escape_character THEN
                        escaped := true;
                    ELSIF character IN ('%', '_') THEN
                        result := result || character;
                    ELSE
                        result := result || translate(character, $from, $to);
                    END IF;
                END LOOP;
                IF escaped THEN
                    result := result || translate(escape_character, $from, $to);
                END IF;
                RETURN result;
            END;
            \$mautic_like\$;
            SQL;
    }
}
