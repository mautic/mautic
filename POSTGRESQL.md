# PostgreSQL support

This fork implements PostgreSQL support on top of [mautic/mautic#15926](https://github.com/mautic/mautic/pull/15926), imported from commit `5bc0dcfd4d`. Implementation and local validation are complete as of 2026-10-15 under the compatibility contract below. Hosted CI and live third-party services remain unverified.

## Requirements and compatibility contract

- PostgreSQL **16 or later**, built with ICU support; PostgreSQL 16 and 18 are the tested versions.
- PHP's `pdo_pgsql` extension and PostgreSQL client tools compatible with the server version. Use UTF8 for PostgreSQL connection configuration.
- The application database role must be able to create its schema objects, compatibility functions and ICU collation. No custom PostgreSQL server extension is required.
- MySQL 8.4 and MariaDB 10.11 remain supported.

The agreed PostgreSQL contract uses standard PostgreSQL regular expressions with a tested common-pattern adapter, and built-in ICU collation for case/accent insensitive equality and uniqueness. It does not promise exact MySQL ICU regex syntax or legacy UCA 4/PAD SPACE behavior (user decisions, 2026-10-15). These limits are described below.

## What changed

| Area | PostgreSQL implementation |
|---|---|
| Connections and Doctrine | Driver-aware configuration, PostgreSQL type mappings, native integer/bigint identities, prefixed and inherited entity mappings, and a maintained DBAL patch. MySQL-only PDO options stay on MySQL connections. |
| Installation | CLI and browser driver/port/charset selection, database creation through a maintenance connection, fixture loading, prefixed tables, and generated columns. |
| Schema and upgrades | Portable historical migrations, index/constraint naming, populated-column backfills, JSON preservation, integrations mappings, and repeatable compatibility refresh migrations. Cold migration commands prepare functions and collation before metadata storage or older migrations use them. |
| Generated columns | All seven stored generated columns, their indexes and Doctrine type comments, including contact email domains and generated calendar fields. |
| Text equality | `mautic_unicode_ci`, a nondeterministic ICU collation with locale `und-u-ks-level1`, on application CHAR/VARCHAR/TEXT columns and native unique indexes. Upgrades preserve generated dependencies and rebuild affected text foreign keys. |
| Search | Shared SQL/DQL adapters for LIKE, negation, escapes and common regex patterns. The LIKE character mapping follows MySQL `utf8mb4_unicode_ci`; native number/date/boolean fields remain searchable, including MySQL-style boolean `1`/`0` matching. Dynamic operators in reports, contact fields, form submissions and repository filters use the same adapter. |
| Reports and dates | Portable grouped reports, nullable distinct tuples, date arithmetic, anniversaries, Sunday-based weeks, timezone offsets and tested DST transitions. Immutable date conversions retain their UTC conversion result. |
| Writes and lifecycle | Explicit-ID and generated-ID upserts, sequence reset handling, platform-aware dumps/restoration, dynamic form-result tables, and backup/reinstall preservation. |
| CI | Required full PHPUnit jobs for PHP 8.2/PostgreSQL 16 and PHP 8.5/PostgreSQL 18, alongside existing MySQL/MariaDB coverage. |

Main implementation sources are [DatabasePlatform.php](app/bundles/CoreBundle/Doctrine/DatabasePlatform.php), [PostgreSqlCompatibilityFunctions.php](app/bundles/CoreBundle/Doctrine/PostgreSqlCompatibilityFunctions.php), [DoctrineEventsSubscriber.php](app/bundles/CoreBundle/EventListener/DoctrineEventsSubscriber.php), the [DBAL patch](patches/doctrine-dbal-3.7.3-pgsql.patch), and migrations [160000](app/migrations/Version20261015160000.php), [170000](app/migrations/Version20261015170000.php) and [180000](app/migrations/Version20261015180000.php).

## Validation

### Full database suites

| Database | PHP | Tests | Assertions | Errors / failures | Status |
|---|---|---:|---:|---:|---|
| PostgreSQL 16 | 8.2.34 | 7,585 | 47,772 | 0 / 0 | Passed, exit 0; 5 skips. |
| PostgreSQL 18.6 | 8.5.11 | 7,585 | 47,764 | 0 / 0 | Passed, exit 0; 6 skips. |
| MySQL 8.4.11 | 8.2.34 | 7,568 | 47,074 | 0 / 0 | Full v6 passed, exit 0; final-source focused checks passed: 190 tests / 430 assertions, plus 2 typed-field tests / 40 assertions. |
| MariaDB 10.11 | 8.5.11 | 7,585 | 47,181 | 0 / 0 | Passed, exit 0; 56 skips. |

All listed runs exited successfully. The MySQL full run predates the final fixes; the final-source focused run exercises the changed search, migration, report, form and date paths. The final typed-field regression also passed separately on MySQL. PostgreSQL skips/incompleteness are the existing category-filter case, legacy Twig cases and unavailable MaxMind credentials; PostgreSQL 18 additionally skips construction of a legacy mismatched-collation FK state that its server rejects. MySQL/MariaDB also skip PostgreSQL-specific infrastructure tests. No application feature was disabled to obtain these results.

### Installation, upgrade and browser evidence

| Check | Completed evidence |
|---|---|
| Fresh browser installation | Chrome 150 completed driver selection, database creation, 125 prefixed application tables, admin creation and login. All seven generated columns, persisted configuration, clean schema comparison, and zero severe console errors were verified. |
| Fresh CLI installation | Actual CLI installation created its requested prefix, 125 application tables, admin user/role and 43 contact fields. Real login reached the dashboard and updated last-login data; schema comparison was clean. Cold ORM inserts generated IDs and email domains correctly on PostgreSQL 16 and 18. A fresh final-source PostgreSQL 16 CLI install also created all 125 tables, seven generated columns and ICU collation; a cold ORM insert generated its ID/domain, schema comparison was clean, and real username/email unique indexes rejected the tested case/accent and ß/ss duplicates. |
| Whole-application upgrade | A populated legacy fixture upgraded through the actual CLI on PostgreSQL 16 and 18 without manually creating the collation/functions. All three compatibility migrations completed, the text foreign key was restored and validated, seven generated columns remained, schema comparison was clean, and repeat migration reported already at latest. All 126 prefixed tables matched the reference fixture: 16 populated tables and 62 rows, with no count or canonical row-hash differences (generated columns excluded). All 489 indexes and 200 foreign keys remained with unchanged definitions. The collation migration rebuilt 109 indexes and the one affected text foreign key; 579 other object IDs were unchanged. |
| Migration regressions | Private schemas cover empty/custom prefixes, unrelated tables, generated-column preservation, unique-conflict rollback, text foreign keys, constraint comments/deferrability, safe abort on unowned foreign-key dependencies, and repeat execution. The legacy mismatched-collation FK fixture runs on PostgreSQL 16; PostgreSQL 18 rejects creation of that legacy state, so only that fixture is skipped there. |
| Browser acceptance | The final synchronized v7 source passed all **40 Codeception tests / 299 assertions** in Chrome 150. The isolated environment uses two HTTPS origins, generated assets, a trusted loopback TLS proxy and a 300-second database-helper timeout. Secure-cookie checks remain enabled. |
| Backup/reinstall | Populated form/submission values, foreign keys, generated columns, indexes and sequences survived repeated backup/reinstall on all four engines: **1 test / 31 assertions** each. Runtime form-result tables and unrelated prefixes are covered. |
| Date/search parity | Common search cases passed **93 tests / 177 assertions** on each engine before the additional repository-filter regression. DST parity passed **1 test / 46 assertions** on each engine, including repeated/nonexistent local times, fractional boundaries, half-hour transitions and a skipped day. |
| Typed custom-field search | Real contact/company number, date and boolean searches plus company lookups passed **2 tests / 40 assertions** on PostgreSQL 16, MySQL 8.4 and MariaDB. The fixture uses database-reset cleanup because MySQL/MariaDB schema changes commit implicitly; its final cleanup setting also passed 2 tests / 40 assertions on PostgreSQL 16. |
| Code quality | Final full PHPStan analysis passed; coding standards passed across **4,804 PHP files**; full Rector dry run passed. Earlier validation passed all **680 Twig templates**, installer JavaScript syntax, workflow YAML and Composer checks. |

The full PHPUnit suites include core, shipped plugins, middleware and static-analysis-rule tests. Representative application coverage includes contact/company CRUD and associations, field lifecycle, CSV import/export, segment rebuilds, campaign execution, queues, email/click/page tracking, dashboards and reports. Shipped plugin coverage includes Focus, TagManager, integration repositories and mapping upgrades. The previously disabled integration-sync test now checks 48 creates, four updates and 52 stored mappings. External CRM APIs are predominantly mocked; live third-party services and arbitrary third-party plugin SQL are not established by these tests.

Hosted CI has not been run for these local changes. DDEV's Podman transport was unreliable during validation, so equivalent PHP commands ran inside the DDEV/isolated application containers through `podman exec`; host PHP was not used.

## Upgrading an existing PostgreSQL installation

Back up the database and schedule a maintenance window: changing text collations takes table locks and rebuilds affected indexes and text foreign keys. Run the normal migrations using the application's configured database:

```bash
ddev exec php bin/console doctrine:migrations:migrate --no-interaction
ddev exec php bin/console doctrine:schema:update --dump-sql
```

The schema comparison should report nothing to update. Repeat migration should report that the application is already at the latest version.

The collation migration operates on mapped application/join tables and owned dynamic form-result tables, including empty and custom prefixes. It preserves unrelated tables. It aborts if an affected text foreign key requires changing an unowned column. Resolve that dependency explicitly before retrying.

Existing unique values that become equal under ICU cause the migration to fail and roll back. The migration never selects or deletes a conflicting row automatically. Review and resolve the conflicting application records, then retry. A successful upgrade requires both preserved data and a clean schema comparison; an already-applied migration marker alone proves neither.

## Search and Unicode limits

Equality and unique constraints use the installed ICU version. Rare Unicode equivalences, ordering, and trailing-space behavior can differ from MySQL's legacy UCA 4 `utf8mb4_unicode_ci` PAD SPACE rules. PostgreSQL must have ICU support. See [PostgreSQL collation support](https://www.postgresql.org/docs/16/collation.html).

LIKE matching uses a generated map of **3,444 MySQL character equivalences**, preserving wildcard widths rather than applying unrestricted accent stripping. It covers SQL, ORM and segment queries, literal wildcard escapes and negation. The data, license and reproducible generator are included in [the Unicode resources](app/bundles/CoreBundle/Resources/unicode/) and [generator](utils/postgresql/generate-unicode-ci.py). PostgreSQL 16 pattern matching uses deterministic adapters around ICU-collated columns.

Common regex boundaries, newline conventions and end anchors are translated and tested. Arbitrary ICU syntax, including Unicode-property escapes and scoped option groups, needs adjustment for PostgreSQL ARE syntax. MariaDB retains its native PCRE differences. See [PostgreSQL pattern matching](https://www.postgresql.org/docs/16/functions-matching.html) and [ICU regex syntax](https://unicode-org.github.io/icu/userguide/strings/regexp.html).

A local LIKE-normalization probe measured 0.426 seconds for 100,000 approximately 100-byte ASCII inputs and 8–9 seconds for the general Unicode mapping path. Validate large non-ASCII scans against your own workload.

## Running database tests

The default DDEV configuration uses MariaDB. PostgreSQL tests require a dedicated disposable database, `pdo_pgsql`, and matching PostgreSQL client tools. PHPUnit creates, truncates and restores application tables. Replace connection values below, then repeat against PostgreSQL 16 and 18:

```bash
ddev exec env DB_DRIVER=pdo_pgsql DB_CHARSET=UTF8 \
  DB_HOST=postgres DB_PORT=5432 DB_NAME=mautictest \
  DB_USER=mautic DB_PASSWD=mautic \
  APP_ENV=test APP_DEBUG=0 MAUTIC_ENV=test SHELL_VERBOSITY=0 \
  php bin/phpunit -c app/phpunit.xml.dist
```

For MySQL/MariaDB, use the corresponding disposable database and `DB_DRIVER=pdo_mysql`. Code-quality commands are `ddev composer phpstan`, `ddev composer cs`, and `ddev exec php bin/rector process --dry-run`.

## Completion record

- [x] Final full PostgreSQL 16/18 and MariaDB runs exit successfully.
- [x] Final MySQL focused regression checks pass after its full-suite pass.
- [x] Whole-upgrade row hashes and unaffected object preservation are verified.
- [x] Final synchronized browser acceptance run passes.
- [x] PostgreSQL typed-field fixture passes with database-reset cleanup.
- [x] Final PHPStan, coding standards and Rector checks pass.
