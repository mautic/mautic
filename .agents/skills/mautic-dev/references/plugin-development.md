# Writing a Mautic plugin

The pattern the traps below share is that the plugin *looks* installed, with tables created, routes registered and files in place, while the thing you wanted does nothing. Each section names the symptom and the cause. `plugins/MauticTagManagerBundle` is the compact core example to copy from; the paths below refer to it.

## Minimal skeleton

`AppKernel` scans `plugins/*` for a `<Dir>/<Dir>.php` bundle class and loads every one it finds. The smallest plugin that installs, registers services and exposes an integration:

```
plugins/MauticExampleBundle/
├── MauticExampleBundle.php                  # extends PluginBundleBase
├── composer.json                            # type "mautic-plugin", for the marketplace
├── Config/
│   ├── config.php                           # name, description, version, author, routes, menu, parameters
│   └── services.php                         # autowired service loading
├── DependencyInjection/
│   └── MauticExampleExtension.php           # loads services.php; name derived from the bundle class
├── Integration/
│   ├── ExampleIntegration.php               # extends BasicIntegration implements BasicInterface
│   └── Support/ConfigSupport.php            # extends ExampleIntegration implements ConfigFormInterface
├── Entity/                                  # optional; schema is generated from metadata
├── EventListener/                           # auto-discovered subscribers
├── Security/Permissions/ExamplePermissions.php   # optional; discovered by directory scan
├── Resources/views/                         # Twig templates
├── Translations/en_US/messages.ini
└── Tests/
```

`config.php` returns an array with `name`, `description`, `version`, `author`, and optional `routes`, `menu` and `parameters`. It carries no `services` key; PHPStan's `NoServicesInBundleConfigRule` fails on one.

`services.php` follows the core pattern:

```php
return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('MauticPlugin\\MauticExampleBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->load('MauticPlugin\\MauticExampleBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
};
```

Copying this file from another plugin and forgetting to change the namespace in both `load()` calls registers nothing and reports nothing. Drop the source plugin's `alias()` lines too, or `NoUnusedServiceAliasRule` fails PHPStan. `MauticCoreExtension::DEFAULT_EXCLUDES` keeps `Config`, `Entity`, `Event`, `DependencyInjection`, `Security`, `Tests` and similar out of autowiring; add your own value objects and anything with a scalar constructor argument that has no default, or the container fails to compile with *"you should configure its value explicitly"*.

Install or refresh with `ddev exec php bin/console mautic:plugins:reload`.

## `services.php` and the DI extension come as a pair

Core's `app/config/services.php` autowires every bundle that has **no** `Config/services.php`. The moment the plugin has one, core skips the bundle and Symfony asks the bundle for its extension class, by convention `DependencyInjection/<Name>Extension.php` where `<Name>` is the bundle class name minus `Bundle` (`MauticExampleBundle` looks for `MauticExampleExtension`). That class loads the file:

```php
class MauticExampleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config')))->load('services.php');
    }
}
```

With `services.php` and no extension, the plugin installs, creates its tables and registers its routes, and **no service exists in the container**: the route resolves and the controller is missing, and subscribers never fire. Check with:

```bash
ddev exec php bin/console debug:container | grep Example
ddev exec php bin/console debug:event-dispatcher <event the plugin listens to>
```

Either have both files or neither. Having `services.php` is preferred, because it gives you control over excludes and aliases.

## Plugin entities need no Doctrine migration

No core plugin has a `Migrations/` folder. `PluginModel` generates the schema by diffing the metadata of entities under `MauticPlugin\*\Entity` and installs it during `mautic:plugins:reload`. All you need is the entity with a static `loadMetadata()` and a `ClassMetadataBuilder`.

Functional tests see plugin tables too, because the test database is built with `doctrine:schema:create`. If a test reports the table missing, the cached schema dump is stale; see *Writing functional tests* in `SKILL.md`.

## Integration classes

`AbstractIntegration` in `PluginBundle` is deprecated. New plugins use `IntegrationsBundle`, where the integration is split into a base class and one support class per capability, each discovered by autoconfigure on its interface:

```php
// Integration/ExampleIntegration.php
class ExampleIntegration extends BasicIntegration implements BasicInterface
{
    public const NAME = 'Example';

    public function getName(): string        { return self::NAME; }
    public function getDisplayName(): string { return 'Example'; }
    public function getIcon(): string        { return 'plugins/MauticExampleBundle/Assets/img/example.png'; }
}

// Integration/Support/ConfigSupport.php
final class ConfigSupport extends ExampleIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string            { return ExampleAuthType::class; }
    public function getFeatureSettingsConfigFormName(): string { return ExampleFeaturesType::class; }
}
```

`ConfigFormInterface` alone gives the plugin an entry under Plugins with a publish toggle. `ConfigFormAuthInterface` adds the credentials tab, `ConfigFormFeatureSettingsInterface` the features tab. `plugins/MauticClearbitBundle/Integration/Support/ConfigSupport.php` is a core example with the auth tab.

## Integration configuration: two tabs, two contracts

The two tabs are built differently, and getting the option contract wrong makes the configuration modal show an alert instead of opening.

| Tab | Your form type receives | Stored in |
|---|---|---|
| Auth (`getAuthConfigFormName()`) | `IntegrationConfigType` passes `'integration' => $integrationObject`, so the form may `setRequired(['integration'])` | `api_keys`, **encrypted** |
| Features (`getFeatureSettingsConfigFormName()`) | `IntegrationFeatureSettingsType` passes only `['label' => false]`, so the form must use `setDefined(['integration'])` at most | `feature_settings`, **plain text** |

Two consequences:

**Secrets belong on the auth tab, always.** The feature settings tab writes plain text to the database, so it is readable in any dump. A one-line test asserting the key field is absent from the feature settings form stops a future refactor from moving it.

**Feature settings are stored nested under an `integration` key**: `['integration' => ['base_url' => ...]]`, not `['base_url' => ...]`. Reading the top level silently returns your defaults and ignores everything the user configured.

The encrypted key comes back **decrypted** when read through the integration entity. Do not give a configuration field a default through Symfony's `data` option: it overrides the stored value on every render, so saving the form resets the field.

## Campaign actions, decisions and conditions

Subscribe to `CampaignEvents::CAMPAIGN_ON_BUILD` and register the event with `addAction()`, `addDecision()` or `addCondition()`. The registration names a second event, yours, that the campaign engine dispatches when the event fires:

```php
public static function getSubscribedEvents(): array
{
    return [
        CampaignEvents::CAMPAIGN_ON_BUILD          => ['onCampaignBuild', 0],
        ExampleEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
    ];
}

public function onCampaignBuild(CampaignBuilderEvent $event): void
{
    $event->addAction('example.do_thing', [
        'label'       => 'mautic.example.campaign.event.do_thing',
        'description' => 'mautic.example.campaign.event.do_thing_descr',
        'eventName'   => ExampleEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        'formType'    => DoThingType::class,          // properties form shown in the builder
    ]);
}

public function onCampaignTriggerAction(CampaignExecutionEvent $event): void
{
    $config = $event->getConfig();                    // what DoThingType collected
    try {
        // ... do the thing for $event->getLead() ...
        $event->setResult(true);
    } catch (\Throwable $e) {
        $event->setFailed($e->getMessage());          // records the reason and routes the contact down the failure path
    }
}
```

`addAction()` requires `label` and either `eventName` or a `callback`. `plugins/MauticFocusBundle/EventListener/CampaignSubscriber.php` is the core example, including `connectionRestrictions` to limit where the action can be attached. Campaign events run from `mautic:campaigns:trigger`, so a functional test needs to run that command (via `ApplicationTester`) after creating the campaign and adding the contact.

## Form fields and submit actions

Subscribe to `FormEvents::FORM_ON_BUILD`. `addFormField()` adds a field type to the form builder, `addSubmitAction()` adds an action that runs on submission, `addValidator()` adds a validator to existing field types. `plugins/MauticSocialBundle/EventListener/FormSubscriber.php` adds a field:

```php
$event->addFormField('plugin.loginSocial', [
    'label'          => 'mautic.plugin.actions.socialLogin',
    'formType'       => SocialLoginType::class,
    'template'       => '@MauticSocial/Integration/login.html.twig',
    'builderOptions' => [
        'addLeadFieldList' => false,
        'addIsRequired'    => false,
        'addDefaultValue'  => false,
        'addSaveResult'    => false,
    ],
]);
```

Guard the registration on the integration being published, as that subscriber does; an unpublished plugin's field otherwise stays selectable in every form.

## Permissions

A class in `Security/Permissions/` named `*Permissions` and extending `AbstractPermissions` is discovered by directory scan when the bundle metadata is built. `Security` is excluded from autowiring, so this is the registration; no service definition or tag is required.

```php
class ExamplePermissions extends AbstractPermissions
{
    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->addStandardPermissions(['items'], false);   // view, edit, create, delete, publish, full
    }

    public function getName(): string { return 'example'; }

    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addStandardFormFields('example', 'items', $builder, $data);
    }
}
```

Permissions are then checked as `example:items:view`, which is the string a menu item's `access` key and `$this->security->isGranted()` take.

## Menu items

The `menu` key in `Config/config.php`:

```php
'menu' => [
    'main' => [
        'example.menu.index' => [
            'id'        => 'mautic_example_index',
            'route'     => 'mautic_example_index',
            'access'    => 'example:items:view',
            'iconClass' => 'ri-hashtag',
            'priority'  => 1,
        ],
    ],
],
```

`main` is the left sidebar; `admin` puts the item under the settings cog. The key is a translation key from `Translations/en_US/messages.ini`.

## Console commands

A command is an ordinary autowired class in `Command/` extending `Command`, and it must carry `#[AsCommand(name: ..., description: ...)]`; PHPStan's `CommandMustHaveAsCommandAttributeRule` rejects one without it. Commands that must not overlap with themselves extend `ModeratedCommand`, which takes a lock in `var/cache/run`.

## Marketplace metadata

The plugin's `composer.json` is what the Mautic marketplace reads:

```json
{
    "name": "acme/plugin-example",
    "description": "Example plugin",
    "type": "mautic-plugin",
    "keywords": ["mautic", "plugin", "integration"],
    "extra": {"install-directory-name": "MauticExampleBundle"},
    "require": {"mautic/core-lib": "^7.0"}
}
```

`type` and `extra.install-directory-name` are the two fields that matter: the first makes the package a plugin, the second is the directory it installs into under `plugins/`.

## The plugin registry lives in the database

`mautic:plugins:reload` writes a row to `plugins` (column `bundle`) and, for a plugin with an integration, to `plugin_integration_settings` (column `name`, the integration's `getName()`). `AppKernel` loads bundles from the filesystem, and the registry is what the Plugins page, the permissions and the integration settings are keyed on; a plugin whose files are gone is flagged `is_missing` on the next reload rather than loaded.

Renaming a plugin therefore means: `mautic:plugins:reload` after the rename registers the new name and flags the old one, but `plugin_integration_settings` for the new name starts empty. That row holds `api_keys` (encrypted with the application secret) and `feature_settings`. Copy them from the old row to the new one; the encrypted value transfers verbatim, because the key is not derived from the integration name. Clear `var/cache` as part of the deploy, since the compiled container still references the old bundle classes.

## CSRF on `/s/` endpoints

Core's `RequestSubscriber` validates `X-CSRF-Token` (token `mautic_ajax_post`, JS global `mauticAjaxCsrf`) on XHR POSTs under `/s/`. Two traps:

- On failure it returns **HTTP 200** with a `flashes` payload, not 403. Tests expecting 403 fail.
- It only validates when `X-Requested-With: XMLHttpRequest` is present. A plain `fetch()` does not send that header, so a naive client **slips past the guard with no error**. Send the header explicitly *and* validate in the controller.

## A malformed JSON body never reaches your controller

FOSRestBundle's `BodyListener` decodes the request body on `kernel.request` and answers `400 Bad Request: "Invalid json message received"` before the controller runs. `$request->toArray()` therefore cannot fail on a malformed body, and a `try/catch` around it is unreachable.

## Custom assets never enter the compiled bundles

Assets added through `CoreEvents::VIEW_INJECT_CUSTOM_ASSETS` (`addScript()`, `addStylesheet()`, `addScriptDeclaration()`) render as their own tags at request time. They are **not** concatenated into `docroot/media/js/*.js`, and **`mautic:assets:generate` is not the fix** when they fail to load. Core's own `GrapesJsBuilderBundle` behaves identically, so grepping `media/js/` for your plugin name is a dead end.

When a custom asset does not load, check in this order: is the integration published (subscribers usually guard on it), is the subscriber in the compiled container (`debug:event-dispatcher`), does the file return 200 over HTTP, and only then the browser.

## Changing an asset does not change its URL

Mautic appends `?v<hash>` to asset URLs and derives that hash from configuration (`secret_key` and version), not from file contents. Editing a JS file or an icon leaves the URL identical, so the browser has no reason to refetch: the new file sits on the server, correct and unreachable until the cache is cleared or the version changes.

## Reading the builder's personalisation tokens

The authoritative list of `{...}` tokens is not a table, it is the answer to an event:

```php
$event = new EmailBuilderEvent($this->translator);
$this->dispatcher->dispatch($event, EmailEvents::EMAIL_ON_BUILD);
$tokens = $event->getTokens(false);   // token => label
```

- **`EmailBuilderEvent`, not its `BuilderEvent` parent.** Core subscribers type-hint the subclass, so a bare `BuilderEvent` throws a `TypeError` from inside the dispatcher.
- **`getTokens(false)`** drops the legacy `{leadfield=...}` aliases, which are the same contact fields under an older spelling.
- **The list is scoped to the current user.** Contact field tokens go through `BuilderTokenHelper`, which filters on `lead:leads:viewown` and `viewother`. From a CLI script with no user the contact fields are silently absent and only the global tokens remain.

## Themes: MJML lives inside a file named `.html.twig`

Mautic only ever looks for `themes/<name>/html/email.html.twig`. What decides whether a theme is MJML is the **content**, not the file name: the builder tests `mjml.includes('<mjml')`. Theme uploads that expect an `email.mjml.twig` are [mautic/mautic#9676](https://github.com/mautic/mautic/issues/9676).

Twig is the envelope, for what Symfony interpolates server-side (`{subject}`, tokens, translation blocks); MJML is the email inside it. Compilation happens in the browser: saving writes the compiled HTML to `emails.custom_html`, which is what reaches the inbox, and the MJML source to `bundle_grapesjsbuilder.custom_mjml`, so the email can be reopened. `config.json` declares only `"builder": ["grapesjsbuilder"]` and `"features": ["email"]`.

## Extending the GrapesJS builder

`GrapesJsBuilderBundle` exposes a global registry, so adding blocks, components or commands needs no fork and no patch:

```javascript
if (!window.MauticGrapesJsPlugins) window.MauticGrapesJsPlugins = [];
window.MauticGrapesJsPlugins.push({
  name: 'MyPlugin',               // required, used as the pluginId
  plugin: MyPlugin,               // required, must be a function
  context: ['email-mjml'],        // [page|email-mjml|email-html]
  pluginOptions: { /* ... */ },
});
```

The array is read in `BuilderService.initGrapesJS()` when the Builder button is clicked, and custom plugins load **last** in `grapesjs.init()`, after `grapesjs-mjml` has run its `resetBlocks`. Without `name`, or with a `plugin` that is not a function, registration is skipped silently with a `console.warn`.

- **Block categories render in registration order**, and `order` is applied as a CSS flex order rather than by moving the DOM node. A plugin loading after `grapesjs-mjml` lands at the bottom of the panel unless its category has an explicit `order`.
- **`element.click()` produces false negatives on GrapesJS toolbars.** They respond to `mousedown`, and a synthetic click does not generate the full event sequence. Use a real click or dispatch `mousedown` explicitly.

Mautic does not expose the editor globally. To reach it for debugging, listen for the `builder:show` event, which receives the instance as its second argument:

```javascript
mQuery('.builder').on('builder:show', function (e, editor) { window.__editor = editor; });
```

## `Mautic.translate()` reads `javascript.ini`

Not `messages.ini`. Keys that exist only in `messages.ini` never reach the browser, and what shows is the fallback string written in the code, which looks like a working translation until someone switches locale.
