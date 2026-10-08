---
name: mautic-dev
description: Use when writing or debugging code in the mautic/mautic repository or in a Mautic plugin - services, entities and migrations, event subscribers, functional tests (MauticMysqlTestCase), PHPStan or CI failures, plugin schema updates, integrations, campaign, form or segment extensions, or preparing a PR.
---

# Mautic development

Read `AGENTS.md` first: it has the DDEV commands, the bundle layout, the coding standards and the PR checklist. This skill adds what it does not cover: the Mautic-specific problems whose symptom points away from the cause, how to verify a change before CI does, and where the existing documentation and code examples are. When an item points to a page or a file, read that instead of reconstructing it.

Conventions change between major versions (event names, entity mapping, plugin structure). The code on the target branch is the reference: copy the pattern from the classes next to the one you are changing.

## Where to look

- Developer documentation: https://devdocs.mautic.org (plugins, extension points, integrations, themes).
- Functional tests: `.github/prompts/write-test.prompt.md`.
- A compact plugin to copy from: `plugins/MauticTagManagerBundle`.

## Target branch

Features go to the current `a.x` branch (`7.x` today), bug fixes to the lowest supported `a.b`, BC breaks to the next major's `c.x`, as `.github/PULL_REQUEST_TEMPLATE.md` explains. Which minors are supported is listed on https://www.mautic.org/mautic-releases, not in the repository.

## Verifying a change

- **PHPStan**: run `ddev composer phpstan`, which builds the test container first. `bin/phpstan` on its own fails with `Container var/cache/test/AppKernelTestDebugContainer.xml does not exist` after the test cache is cleared.
- **PHPStan and plugin entities**: `tests/object-manager.php` boots the kernel in `prod`, so Doctrine's mappings come from the compiled `var/cache/prod`. `The class ... was not found in the chain configured namespaces` for a plugin entity means that cache was built before the entity existed: run `ddev exec rm -rf var/cache/prod var/phpstan-cache`.
- **Mautic's own PHPStan rules** live in `utils/phpstan/src/Rule/`, and each error message says what to do instead. In a controller that inherits `CommonController`'s constructor, the fix for `getModel('...')`, `$this->container->get()` or the entity manager's `getRepository()` is a `#[Required]` method, because redeclaring the constructor to add dependencies trips `NoParentConstructorForwardingRule`. Its name is fixed by `AutowireMethodNameMustMatchClassRule` (`autowireLeadController()` in `app/bundles/LeadBundle/Controller/LeadController.php`, `autowireAssetAjaxController()` in the Asset bundle's `AjaxController`). A controller that declares its own constructor takes the dependencies there.
- **Schema**: run a new migration against a database that does not have the change yet, then `ddev exec php bin/console doctrine:schema:update --dump-sql` must print nothing for the entities you changed. Anything it prints is a change the migration does not cover. On a database that already has the change, `preUpAssertions()` skips the migration and the check proves nothing.
- **Scaffolded files**: some root files are copies of templates in `app/assets/scaffold/files/`, mapped in `app/composer.json` under `extra.mautic-scaffold.file-mapping` (`.gitignore` is one). Change both, or the `scaffolded files mismatch` CI job fails.
- **Rector** runs in CI as `bin/rector --dry-run`. Rebasing an old branch can surface rules that did not exist when it was written.
- **SonarCloud** fails on duplicated new code. Near-identical test methods trip it; extract a helper.
- **The PR description** follows `.github/PULL_REQUEST_TEMPLATE.md`. After the PR is opened, Promptless drafts the documentation PR from it, so describe the change completely and give real steps to test.

## Functional tests

Start from `.github/prompts/write-test.prompt.md`. What it does not say:

- `bin/phpunit` without `-c app/phpunit.xml.dist` fails on a missing `KERNEL_CLASS`, which looks like a broken test.
- The test container replaces the HTTP client with a `MockHttpClient` that answers 200 with an empty body (`app/config/config_test.php`). A test that seems to reach an external service never does.
- The test database is built once and cached as a SQL dump in the test cache directory. An entity added after that produces `Base table or view not found`, a column `Unknown column`. Run `ddev exec rm -rf var/cache` and rerun; do not create the schema in the test.
- Table names are prefixed under test (`test_`, from `.env.test`). Raw SQL takes the name from `$this->em->getClassMetadata(X::class)->getTableName()`. A test must not define `MAUTIC_TABLE_PREFIX` itself; PHPStan rejects it.
- The test schema comes from the entity metadata, and core migrations are only marked as executed. No functional test runs a migration, so test one by running it yourself (see "Core migrations").

## Core migrations

- Create the file with `ddev exec php bin/console doctrine:migrations:generate`. The template already extends `PreUpAssertionMigration`: add one `skipAssertion()` per statement in `preUpAssertions()`, and take table names from `$this->getPrefixedTableName()`.
- Run a single migration by its full class name: `ddev exec php bin/console doctrine:migrations:execute --up 'Mautic\Migrations\Version20260924080829'`.
- Then check the schema as described under "Verifying a change".

## Backward compatibility

What counts as a BC break, and how to write code that avoids them, is in https://contribute.mautic.org/en/latest/contributing/developer.html#backward-compatibility-breaks.

## Extension points

| To add | Documentation | Example in core |
|---|---|---|
| Campaign action, decision or condition | https://devdocs.mautic.org/en/latest/plugin_extensions/campaigns.html | `plugins/MauticFocusBundle/EventListener/CampaignSubscriber.php` |
| Form field, submit action or validation | https://devdocs.mautic.org/en/latest/plugin_extensions/forms.html | `app/bundles/AssetBundle/EventListener/FormSubscriber.php` (submit action) |
| Report | https://devdocs.mautic.org/en/latest/plugin_extensions/reports.html | `plugins/MauticFocusBundle/EventListener/ReportSubscriber.php` |
| Contact timeline entry | https://devdocs.mautic.org/en/latest/plugin_extensions/contacts.html | `plugins/MauticFocusBundle/EventListener/LeadSubscriber.php` |
| Permissions | https://devdocs.mautic.org/en/latest/plugins/permissions.html | `plugins/MauticTagManagerBundle/Security/Permissions/TagManagerPermissions.php` |
| Menu item | https://devdocs.mautic.org/en/latest/plugins/config.html | `plugins/MauticTagManagerBundle/Config/config.php` |
| Segment filter | none yet, see below | `app/bundles/PointBundle/EventListener/SegmentFilterSubscriber.php` |

The Point bundle's subscriber adds a filter to the segment builder and registers how it queries, using a query builder from `app/bundles/LeadBundle/Segment/Query/Filter/`. A filter with a fixed set of options renders as a dropdown only if its `properties.type` is `select` or `multiselect`, with the options in `properties.list`; any other custom type renders as a text input (`TypeOperatorSubscriber::onSegmentFilterFormHandleSelect()`).

## Plugins

Start with https://devdocs.mautic.org/en/latest/plugins/getting_started.html. Most of the problems below produce no error: the plugin installs, its tables and routes exist, and the feature still does nothing.

- **Services.** `Config/services.php` needs a `DependencyInjection/<Name>Extension.php` to load it (https://devdocs.mautic.org/en/latest/plugins/autowiring.html). Once a plugin has `services.php`, core stops autowiring it (`app/config/services.php`), so without that class no service exists: the route resolves, the controller is missing, and subscribers never fire. `<Name>` is the bundle class name without `Bundle`. When copying from `plugins/MauticTagManagerBundle`, rename the extension class and every namespace, but keep the `mautic.integration.<name>` aliases: the Plugins page finds integrations by that service id (`app/bundles/PluginBundle/Helper/IntegrationHelper.php`). Check with `ddev exec php bin/console debug:container | grep <PluginName>`.
- **Schema on install** is generated from the entity metadata by `mautic:plugins:reload`. No migration is needed for it.
- **Schema changes on update** are classes in the plugin's `Migrations/` directory extending `Mautic\IntegrationsBundle\Migration\AbstractMigration` (https://devdocs.mautic.org/en/latest/plugins/database.html#plugin-schema-migrations). They never run on install. They run on `mautic:plugins:reload` only when the `version` in `Config/config.php` is higher than the installed one, and every file runs on every later update, so `isApplicable()` must return false once the change exists. That includes sites installed fresh at the new version, whose schema came from the metadata. The plugin's `config.php` is read into the compiled container, so clear the cache before the reload or it does not see the new version.
- **Integration settings.** New integrations use `IntegrationsBundle` (https://devdocs.mautic.org/en/latest/plugin_integrations/integrations.html); see `plugins/MauticTagManagerBundle/Integration/`. The auth tab is stored encrypted in `api_keys` and the features tab in plain text in `feature_settings`, so secrets go on the auth tab. The features form is nested under an `integration` key: read `getFeatureSettings()['integration']['your_field']`, because the top level returns nothing (the example in the developer documentation reads the top level). The auth form receives an `integration` option; the features form receives none, so declaring it required there makes the configuration modal show an alert instead of opening.
- **CSRF on `/s/` routes.** Send `X-Requested-With: XMLHttpRequest` and `X-CSRF-Token` (the `mauticAjaxCsrf` value) with every AJAX POST. Core validates the token only on requests that carry `X-Requested-With`, so an endpoint that changes data should reject POSTs without it. A failed check returns 200 with a `flashes` payload, not 403.
- **Malformed JSON bodies** get a 400 from FOSRest's body listener before the controller runs, so a `try/catch` around `$request->toArray()` never triggers.
- **Custom assets** added through `CoreEvents::VIEW_INJECT_CUSTOM_ASSETS` render as separate tags and are not part of `media/js`; `mautic:assets:generate` does not touch them. The `?v` hash on asset URLs comes from configuration, not file contents, so an edited file keeps its URL.
- **`Mautic.translate()`** reads the `javascript` domain (`javascript.ini`), not `messages.ini`.
