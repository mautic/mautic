# Mautic architecture reference

Complements `AGENTS.md`, which has the bundle layout and the command list. This is the layer below: how the pieces are wired and what a change to them costs.

## Service registration

Every bundle and plugin declares its services in `Config/services.php`, with autowiring and autoconfigure on:

```php
return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('Mautic\\ExampleBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->load('Mautic\\ExampleBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    // String ids only where legacy code still looks a service up by name.
    $services->alias('mautic.example.model.item', Mautic\ExampleBundle\Model\ItemModel::class);
};
```

`MauticCoreExtension::DEFAULT_EXCLUDES` keeps `Config`, `Entity`, `Event`, `DependencyInjection`, `Security`, `Tests`, `Views` and similar out of autowiring. A `services` key in `Config/config.php` fails PHPStan (`NoServicesInBundleConfigRule`), and an alias nothing references fails too (`NoUnusedServiceAliasRule`).

Service constructors can change freely; that is not a backward-compatibility break, because nothing outside the container references them by signature.

## Event system

1. Define constants in a bundle-level class, for example `ExampleEvents`.
2. Create event objects extending `Symfony\Contracts\EventDispatcher\Event`.
3. Dispatch from models or controllers.
4. Subscribe in `EventListener/` classes implementing `EventSubscriberInterface`.

`ddev exec php bin/console debug:event-dispatcher <event name>` lists who is subscribed, from the compiled container. When a subscriber appears to do nothing, check here before checking the code.

## Entity mapping

Entities are mapped through a static `loadMetadata()` with `ClassMetadataBuilder`, not attributes or annotations. Repositories extend `CommonRepository`. Entities that need `dateAdded`, `dateModified`, `createdBy` and `isPublished` extend `FormEntity`.

```php
public static function loadMetadata(ORM\ClassMetadata $metadata): void
{
    $builder = new ClassMetadataBuilder($metadata);
    $builder->setTable('your_table')
        ->setCustomRepositoryClass(YourRepository::class);
    $builder->addId();
    $builder->createManyToOne('form', 'Form')
        ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
        ->build();
    $builder->addLead(true, 'SET NULL');
}
```

## Controllers and models

- `CommonController`: base controller with the Mautic helpers.
- `AbstractStandardFormController`: CRUD with standard form handling.
- `AjaxController`: AJAX endpoints.

Every action declares `Response` in its return type (`ControllerMethodMustReturnResponseRule`). Models extend `AbstractCommonModel` and are the service layer: `getEntity()`, `saveEntity()`, `deleteEntity()`, `getRepository()`. Controllers receive models and repositories as typed properties set by a `#[Required] autowire<ControllerName>()` method, never through `getModel('name')` and never by redeclaring `CommonController`'s constructor (see the PHPStan table in `SKILL.md`).

## Backward compatibility

A BC break is any of:

- Removing or renaming a public or protected method in a non-final class.
- Changing a method signature (parameters, return types).
- Altering method behaviour.
- Adding a method to an existing interface.
- Modifying a Twig template that themes override.

Not a BC break: changing a service constructor (autowired).

Writing BC-conscious code: new classes `final` by default, new methods `private` by default, composition over inheritance. Do not drop `final` only to make unit testing easier.

## Database migrations

Core schema changes need a migration in `app/migrations/`; plugin entities do not (see `plugin-development.md`).

```bash
ddev exec php bin/console doctrine:migrations:generate
ddev exec php bin/console doctrine:migrations:execute --up VERSION
ddev exec php bin/console doctrine:migrations:execute --down VERSION
```

Migrations extend `Mautic\CoreBundle\Doctrine\PreUpAssertionMigration`. Its `preUp()` is `final`; the hook is `preUpAssertions()`, where each `skipAssertion()` describes a state that means the migration already ran. The migration is skipped only when every assertion holds, which is what makes a partially applied migration safe to rerun:

```php
final class Version20260101000000 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName('leads'))->hasColumn('new_column'),
            'Column leads.new_column already exists'
        );
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('ALTER TABLE %s ADD new_column VARCHAR(255) DEFAULT NULL', $this->getPrefixedTableName('leads')));
    }
}
```

Table names always go through `$this->getPrefixedTableName()`; a literal ignores the configured prefix. `app/migrations/Version20260726100000.php` is a complete recent example, including a `down()`.

## Functional test skeleton

```php
<?php

declare(strict_types=1);

namespace Mautic\ExampleBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class ExampleFunctionalTest extends MauticMysqlTestCase
{
    public function testSomething(): void
    {
        $entity = new SomeEntity();
        $this->em->persist($entity);
        $this->em->flush();

        $this->client->request('GET', '/s/example');
        $this->assertResponseIsSuccessful();
    }
}
```

Codeception acceptance tests need `ddev exec php bin/codecept build` once, then `ddev exec php bin/codecept run acceptance [Cest[:method]]`.

## Reviewing someone else's PR

```bash
gh pr checkout <number>
ddev exec rm -rf var/cache/*
```

Then follow the PR's "Steps to test" and submit the review on GitHub. The checklist: correct implementation with test coverage, no new bugs, backward compatibility kept, coding standards followed, testing steps reproducible.

## Keeping a development install current

```bash
ddev composer install
ddev exec php bin/console cache:clear
ddev exec php bin/console doctrine:migrations:migrate
ddev exec php bin/console doctrine:schema:update --dump-sql   # preview
ddev exec php bin/console doctrine:schema:update --force      # apply, dev only
```
