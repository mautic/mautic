---
name: mautic-dev
description: Use when writing or debugging code in the mautic/mautic repository or in a Mautic plugin - bundles, services, Doctrine entities and migrations, event subscribers, functional tests (MauticMysqlTestCase), PHPStan or CI failures, segment filters, campaign or form extensions, integrations, permissions, the GrapesJS builder, or preparing a PR and choosing its target branch.
---

# Mautic development

`AGENTS.md` at the repository root carries the essentials: DDEV commands, bundle layout, coding standards, platform requirements and the PR checklist. Read it first. This skill adds what AGENTS.md does not cover: the traps whose failure points somewhere other than its cause, the checks CI runs that are not in the three commands, and the extension points a plugin or core change reaches for most often.

All commands run through DDEV, as AGENTS.md requires.

## Which branch to target

The PR template explains the scheme: `a.x` for features and enhancements on the current major (`7.x` today), `a.b` for bug fixes on the lowest supported minor, `c.x` for backward-compatibility breaks on the next major. Which minors are supported is not recorded in the repository; `SECURITY.md` points to the [Releases page](https://www.mautic.org/mautic-releases), and `git ls-remote --heads upstream` shows which `a.b` branches exist. Bug fixes land on the lowest applicable branch and are merged upwards by the maintainers.

## Quality tools: what actually runs where

**PHPStan reads two environments, and neither is obvious.** `phpstan.neon` points `containerXmlPath` at `var/cache/test/AppKernelTestDebugContainer.xml` and `objectManagerLoader` at `tests/object-manager.php`, which boots the kernel in `prod`. So the container comes from the test cache and the Doctrine entity namespace chain comes from the prod database. Two failures follow, and both look like code errors:

- `Container var/cache/test/AppKernelTestDebugContainer.xml does not exist` after clearing the test cache. Regenerate with `ddev exec "APP_ENV=test APP_DEBUG=1 php bin/console cache:warmup"`.
- `The class ... was not found in the chain configured namespaces` for a plugin entity. The plugin is not registered in the prod database: run `ddev exec "APP_ENV=prod php bin/console mautic:plugins:reload"`, then clear `var/cache/prod` and `var/phpstan-cache`.

**PHPStan and functional tests need the database**, so they run inside DDEV. Binaries live in `bin/`, not `vendor/bin/` (Mautic sets `bin-dir`). A git worktree has no `vendor/` and no DDEV project; push and let CI run PHPStan and PHPUnit there.

**Mautic ships its own PHPStan rules** in `utils/phpstan/src/Rule/`, enabled in `phpstan.neon`. They encode the dependency-injection conventions and fail CI on patterns that older Mautic code and most tutorials still show:

| Rule | Rejects | Do instead |
|---|---|---|
| `NoServicesInBundleConfigRule` | A `services` key in `Config/config.php` | Declare services in the autowired `Config/services.php` |
| `NoContainerGetRule` | `$this->container->get(...)` | Inject the dependency (constructor, or `autowire*()` method in a controller) |
| `NoGetModelWithStringInControllerRule` | `$this->getModel('lead')` in a controller | Inject the model through the controller's `autowire*()` method |
| `NoEntityManagerGetRepositoryRule` | `$em->getRepository(Lead::class)` | Inject the repository as a typed dependency |
| `NoGetRepositoryWithEntityInRepositoryRule` | The same, inside a repository | Inject the other repository |
| `PreferInterfaceInConstructorRule` | A concrete class where an interface exists (`Router` vs `RouterInterface`) | Type the interface |
| `NoNullableServiceInConstructorRule` | `?SomeService $service` | Services are always provided; make it non-nullable |
| `NoServiceInMethodParameterRule` | A service passed as a method argument | Inject it in the constructor or an `autowire*()` method |
| `NoServiceJugglingRule` | `$this->service` handed to another object's method | Inject the service where it is used |
| `NoParentConstructorForwardingRule` | Constructor parameters forwarded to `parent::__construct()` | Do not redeclare the parent constructor; take own dependencies in an `autowire*()` method |
| `NoRequiredMethodWithConstructorInControllerRule` | `#[Required]` setter next to a constructor | Move the dependency into the constructor |
| `AutowireMethodNameMustMatchClassRule` | `#[Required]` method not named `autowire<ClassName>()` | Rename it; the name keeps it unique in the class hierarchy |
| `ControllerMethodMustReturnResponseRule` | An action without `Response` in its return type | Declare the return type |
| `CommandMustHaveAsCommandAttributeRule` | A `Command` subclass without `#[AsCommand]` | Add the attribute with `name` and `description` |
| `ConstraintMustHaveAttributeRule` | A `Constraint` subclass without `#[\Attribute]` | Add it |
| `AbstractClassNameMustBeAbstractRule` | `Abstract*` class not declared abstract | Add `abstract` or rename |
| `NoTablePrefixDefinitionInTestsRule` | `define('MAUTIC_TABLE_PREFIX', ...)` in a test | The test bootstrap defines it |
| `NoUnusedServiceAliasRule` | An alias in `services.php` nothing references | Remove it |

Running `ddev composer phpstan` locally before pushing catches all of these. The injection pattern for a controller, which extends `CommonController` and must not redeclare its wide constructor, is a `#[Required]` method named after the class:

```php
#[Required]
public function autowireAcmeController(LeadModel $leadModel, LeadRepository $leadRepository): void
{
    $this->leadModel      = $leadModel;
    $this->leadRepository = $leadRepository;
}
```

`app/bundles/LeadBundle/Controller/LeadController.php` is the core example. Services that are not controllers take their dependencies in the constructor.

**Rector runs in CI** with `bin/rector --dry-run`, over code and tests. Rebasing an old branch onto the current target can surface newer rules, for example `AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector`: use `$this->assertResponseIsSuccessful()`, not `assertTrue($response->isOk())`.

**SonarCloud has its own quality gate**, separate from codecov and PHPUnit, and it fails on duplicated new code. Functional tests with near-identical methods trip it; extract a shared helper.

**The PR description is enforced.** Follow `.github/PULL_REQUEST_TEMPLATE.md`: the Q/A table, a `## Description`, and numbered `### 📋 Steps to test this PR:`. Maintainers stall PRs whose description skips the template. Documentation PRs are opened automatically by Promptless once the PR exists; review that PR after the code merges, and put its link in the table.

**If `cache:clear` itself fails** (missing dependency, broken container), remove the directory:

```bash
ddev exec rm -rf var/cache/*
```

**Fixture data wipes the whole database.** `ddev exec php bin/console doctrine:fixtures:load` (short form `d:f:l`) is for a development install only.

## Common patterns

- **Services**: every bundle and plugin declares services in `Config/services.php`, autowired and autoconfigured. `Config/config.php` carries `name`, `description`, `version`, `author`, `routes`, `menu`, `parameters` and `permissions`, never services. A class implementing `EventSubscriberInterface` in `EventListener/` needs no registration beyond that.
- **Events**: constants in a bundle-level `<Name>Events` class, event objects in `Event/`, subscribers in `EventListener/`. `debug:event-dispatcher <event>` shows who is subscribed in the compiled container.
- **Entities**: mapping through a static `loadMetadata(ORM\ClassMetadata $metadata)` using `ClassMetadataBuilder`, not attributes. Repositories extend `CommonRepository`.
- **Migrations**: extend `PreUpAssertionMigration`. Its `preUp()` is `final`; implement `preUpAssertions()` with `skipAssertion()` so a partially applied migration is skipped rather than failed, and take table names from `$this->getPrefixedTableName()`.
- **Controllers**: extend `CommonController` or `AbstractStandardFormController`. **Models**: extend `AbstractCommonModel`.

`references/architecture.md` expands each of these with code, and covers backward-compatibility rules.

## Writing functional tests

Functional tests extend `MauticMysqlTestCase` and get `$this->em`, `$this->client` and the full container. Four traps, each of which fails somewhere other than its cause:

- **The PHPUnit config lives at `app/phpunit.xml.dist`.** Run `ddev exec php bin/phpunit -c app/phpunit.xml.dist <path>`. Without the config flag, tests fail on a missing `KERNEL_CLASS` env var, which reads as a broken test rather than a missing flag.
- **The test container replaces `HttpClientInterface` with a `MockHttpClient`** that answers HTTP 200 with an empty body (`app/config/config_test.php`). A test that appears to call an external service silently gets that instead. To exercise a real endpoint, build the client around `HttpClient::create()` explicitly.
- **The test schema is cached and never invalidated.** The first run builds the database with `doctrine:schema:create`, which includes plugin entities, then dumps it to `var/cache/test/fresh_db-<dbname>.sql` and replays that dump on every later run. An entity or column added after the dump exists produces `Base table or view not found`. Delete the dump and rerun:

  ```bash
  ddev exec rm -f var/cache/test/fresh_db-*.sql var/cache/test/reset_db-*.sql
  ```

  The dump lives in the test kernel's cache directory, so an install that sets `cache_path` in `config/local.php` has it there instead. Do not create the table by hand with `SchemaTool` or disable `$useCleanupRollback`; both work around the symptom and cost the transaction cleanup.
- **Table names are prefixed under test** (`test_`, from `.env.test`). Raw SQL in a fixture must take the name from `$this->em->getClassMetadata(X::class)->getTableName()`. A literal is right in production and wrong in the test, and defining the prefix constant yourself fails PHPStan.

To run a console command inside a test, use `ApplicationTester`:

```php
$application = new Application(self::$kernel);
$application->setAutoExit(false);
$tester = new ApplicationTester($application);

$exitCode = $tester->run([
    'command' => 'mautic:segments:update',
    '-i'      => $segment->getId(),
]);
$this->assertSame(0, $exitCode, $tester->getDisplay());
```

## Building a plugin

Two rules decide whether a plugin's services exist at all:

- **Without `Config/services.php`**, core's `app/config/services.php` autowires the whole bundle for you.
- **With `Config/services.php`**, core skips the bundle and nothing loads the file unless the plugin has `DependencyInjection/<Name>Extension.php`, where `<Name>` is the bundle class name minus `Bundle`. Missing it, the plugin installs, creates its tables and registers its routes, and no service exists: the route resolves and the controller is missing.

Plugin entities need **no Doctrine migration**. `PluginModel` diffs the metadata of entities under `MauticPlugin\*\Entity` and installs the schema during `mautic:plugins:reload`.

`references/plugin-development.md` has the skeleton, the integration classes, campaign and form extension, permissions, menu, commands, the marketplace `composer.json`, and the runtime traps (CSRF, custom assets, builder tokens, GrapesJS).

## Extension points

| To add | Subscribe to | Call | Example in core |
|---|---|---|---|
| Campaign action, decision or condition | `CampaignEvents::CAMPAIGN_ON_BUILD` | `$event->addAction()` and handle your own trigger event | `plugins/MauticFocusBundle/EventListener/CampaignSubscriber.php` |
| Form field or submit action | `FormEvents::FORM_ON_BUILD` | `$event->addFormField()` / `addSubmitAction()` | `plugins/MauticSocialBundle/EventListener/FormSubscriber.php` |
| Segment filter | `LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE` + two more | See `references/segment-filters.md` | `LeadBundle/EventListener/FilterOperatorSubscriber.php` |
| Report | `ReportEvents::REPORT_ON_BUILD` / `REPORT_ON_GENERATE` | `$event->addTable()` | `plugins/MauticFocusBundle/EventListener/ReportSubscriber.php` |
| Global search results | `CoreEvents::GLOBAL_SEARCH` | `$event->addResults()` | `plugins/MauticTagManagerBundle/EventListener/SearchSubscriber.php` |
| Contact timeline entry | `LeadEvents::TIMELINE_ON_GENERATE` | `$event->addEvent()` | `plugins/MauticFocusBundle/EventListener/LeadSubscriber.php` |
| Script or stylesheet on every page | `CoreEvents::VIEW_INJECT_CUSTOM_ASSETS` | `$event->addScript()` | `plugins/GrapesJsBuilderBundle/EventSubscriber/AssetsSubscriber.php` |
| Permission | A `*Permissions` class in `Security/Permissions/` | Discovered by directory scan, no registration | `plugins/MauticTagManagerBundle/Security/Permissions/TagManagerPermissions.php` |
| Menu item | The `menu` key in `Config/config.php` | `route`, `access`, `iconClass`, `priority` | `plugins/MauticTagManagerBundle/Config/config.php` |

## Reference files

- `references/architecture.md`: service registration, event system, entity mapping, controllers and models, backward-compatibility rules, migrations, functional test skeleton, reviewing PRs.
- `references/plugin-development.md`: plugin skeleton, DI extension, integration classes and configuration tabs, campaign and form extension, permissions, menu, commands, marketplace metadata, plugin registry, CSRF, custom assets, builder tokens, MJML themes, GrapesJS.
- `references/segment-filters.md`: the three events, the query builder types, the `$isSelect` gotcha and a foreign-table example.
