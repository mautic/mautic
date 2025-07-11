# Backwards compatibility breaking changes

## Removed features
- The Gated Video feature was removed as it was used only in the Legacy builder. See https://github.com/mautic/mautic/pull/14284
- The Froala editor got removed due to security issues of the old version we couldn't update due to licencing issues. It was used in the legacy builder only.

## BC breaks in the code

### Javascript

As the legacy builder was removed these JS libraries were removed as well:
- Froala (outdated with security vulnerabilities)
- CodeMirror JS (still installed in the GrapesJS plugin, but not part of Mautic itself)
- Jquery UI - Safe Blur
- Modernizr as not necessary anymore as the modern browsers support open standards

### PHP
- Multiple method signatures changed to improve type coverage. Some forced by dependency updates, some in Mautic itself. Run `composer phpstan` when your plugin is installed to get the full list related to your plugin.
- `Mautic\PointBundle\Form\Type\GenericPointSettingsType` was removed. See https://github.com/mautic/mautic/pull/13904
- Changes necessary for https://symfony.com/blog/new-in-symfony-5-3-guard-component-deprecation, see https://github.com/mautic/mautic/pull/14219
    - `Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory` was removed.
    - The `friendsofsymfony/oauth-server-bundle` package was replaced with a maintained fork `klapaudius/oauth-server-bundle`
    - The `lightsaml/sp-bundle` package was replaced with a maintained fork `javer/sp-bundle`
- Deprecated `Mautic\LeadBundle\Model\FieldModel::getUniqueIdentiferFields` and `Mautic\LeadBundle\Model\FieldModel::getUniqueIdentifierFields` were removed. Use `Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier::getFieldsWithUniqueIdentifier` instead.
- The signature for the `Mautic\PluginBundle\Integration\AbstractIntegration::__construct()` had to be changed as the `SessionInterface` service no longer exists in Symfony 6. So it was removed from the constructor and session is being fetched from the `RequestStack` instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getRequest` use dependency injection with RequestStack instead.
- Removed PluginBundleBase::onPluginInstall, listen to the PluginEvents::ON_PLUGIN_INSTALL instead.
- Removed PluginBundleBase::onPluginUpdate, listen to the PluginEvents::ON_PLUGIN_UPDATE instead.
- Moved PluginBundleBase::installPluginSchema to \Mautic\PluginBundle\Bundle\PluginDatabase::installPluginSchema. MauticFactory is removed as parameter.
- Removed PluginBundleBase::updatePluginSchema, as method was not recommended, and produced bad results.
- Removed PluginBundleBase::onPluginUninstall, as method was empty.
- Moved PluginBundleBase::dropPluginSchema to \Mautic\PluginBundle\Bundle\PluginDatabase::dropPluginSchema. Removed MauticFactory as parameter.
- Removed AbstractPluginBundle::onPluginUpdate, now listening to the PluginEvents::ON_PLUGIN_UPDATE.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getDatabase` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getHelper` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getDebugMode` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getMauticBundles` use BundleHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getKernel` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getParameter` use DI with the `\Mautic\CoreBundle\Helper\CoreParametersHelper` instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getVersion` use dependency injection with KernelInterface, which will retrieve \AppKernel, then invoke getVersion() method.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getPluginBundles` use BundleHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getBundleConfig` use BundleHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getUser` use UserHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getSystemPath` use PathsHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getTranslator` use Translator instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getRouter` use Router or UrlGeneratorInterface instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getLocalConfigFile` use dependency injection with KernelInterface, which will retrieve \AppKernel, then invoke getLocalConfigFile().
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getEnvironment` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getIpAddress` use IpLookupHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getSecurity` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::get` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::serviceExists` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getSecurityContext` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getDispatcher` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getMailer` use dependency injection instead with \Mautic\EmailBundle\Helper\MailHelper.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getIpAddressFromRequest` use dependency injection with \Mautic\CoreBundle\Helper\IpLookupHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getDate` use \Mautic\CoreBundle\Helper\DateTimeHelper instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getLogger` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getTwig` use DI with the `\Twig\Environment` instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getTheme` use DI with the `\Mautic\CoreBundle\Helper\ThemeHelper` instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getInstalledThemes` use DI with the `\Mautic\CoreBundle\Helper\ThemeHelper` instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getEntityManager` use dependency injection instead.
- Removed `Mautic\CoreBundle\Factory\MauticFactory::getModel` use dependency injection instead. Quick replacement will be `Mautic\CoreBundle\Factory\ModelFactory::getModel`, but most sustainable is to use dependency injection.
- Removed `Mautic\CoreBundle\Factory\MauticFactory`, `'mautic.factory'` service.
- Removed `Mautic\CampaignBundle\Entity::getEventsByChannel()` as unused and buggy. No replacement
- Removed `Mautic\CoreBundle\Test::createAnotherClient()` as unused. No replacement.
- Removed `Mautic\NotificationBundle\Entity::getLeadStats()` as unused and buggy. No replacment
- Removed `Mautic\WebhookBundle\Entity::removeOldLogs()` as it was deprecated. Use `removeLimitExceedLogs()` instead.
- Removed `Mautic\PageBundle\Entity::findByIds()` as unused and buggy. Use Doctrine's `findAllBy(['id' => [1,2]])` instead.
- Removed `Mautic\PluginBundle\Controller::getIntegrationCampaignsAction()` as unused and buggy together with JS function `Mautic.getIntegrationCampaigns`
- Removed `Mautic\CoreBundle\Tests\Functional\Service::class` as unused and testing 3rd party code instead of Mautic.
- Removed `Mautic\CoreBundle\Doctrine\TranslationMigrationTrait` as unused and deprecated.
- Removed `Mautic\CoreBundle\Doctrine\VariantMigrationTrait` as unused and deprecated.
- Removed `Mautic\IntegrationsBundle\Form\Type\NotBlankIfPublishedConstraintTrait` as unused.
- Removed `Mautic\IntegrationsBundle\Form\Type\Auth\BasicAuthKeysTrait` as unused.
- Removed `Mautic\IntegrationsBundle\Form\Type\Auth\Oauth1aTwoLeggedKeysTrait` as unused.
- Removed `Mautic\CoreBundle\Helper\CoreParametersHelper::getParameter()`. Use `Mautic\CoreBundle\Helper\CoreParametersHelper::get()` instead.
- Removed these services as the authentication system in Symfony 6 has changed and these services were using code that no longer existed.
    - `mautic.user.form_guard_authenticator` (`Mautic\UserBundle\Security\Authenticator\FormAuthenticator::class`)
    - `mautic.user.preauth_authenticator` (`Mautic\UserBundle\Security\Authenticator\PreAuthAuthenticator::class`)
    - `mautic.security.authentication_listener` (`Mautic\UserBundle\Security\Firewall\AuthenticationListener::class`)
- The `GrapesJsData` class was moved from `Mautic\InstallBundle\InstallFixtures\ORM` namespace to `MauticPlugin\GrapesJsBuilderBundle\InstallFixtures\ORM` as plugins should not be coupled with core bundles.
- The `lightsaml/sp-bundle` package was replaced with a maintained fork `lightsaml2/sp-bundle`
- `Mautic\PageBundle\Form\Type\PagePublishDatesType` was removed.
- `getSessionName` was removed from `Mautic\PageBundle\Helper\TrackingHelper` No session for anonymous users. Use `getCacheKey`.
- `getSession` was removed from `Mautic\PageBundle\Helper\TrackingHelper` No session for anonymous users. Use `getCacheItem`.
- `updateSession` was removed from `Mautic\PageBundle\Helper\TrackingHelper` No session for anonymous users. Use `updateCacheItem`.
- `getNewVsReturningPieChartData` was removed from `Mautic\PageBundle\Model\PageModel`. Use `getUniqueVsReturningPieChartData()` instead.
- `Mautic\PageBundle\Helper\PointActionHelper::validateUrlHit` is no longer static.
- Replaced the `tightenco/collect:^8.16.0` package with `illuminate/collections:^10.48`.
- Form submissions now store data without HTML entity encoding instead of with encoded entities (e.g., `R&R` instead of `R&#x26;R`)
- `FormFieldHelper::getTypes` signature has been changed
- `FormFieldHelper::getFieldFilter` signature has been changed and now returns `string` filter by default

## Most notable changes required by Symfony 6

### Getting a value from request must be scalar

Meaning arrays cannot be returned with the `get()` method. Example of how to resolve it:
```diff
- $asset = $request->request->get('asset') ?? [];
+ $asset = $request->request->all()['asset'] ?? [];
```

### ASC contants replaced with enums in Doctrine
```diff
- $q->orderBy($this->getTableAlias().'.dateAdded', \Doctrine\Common\Collections\Criteria::DESC);
+ $q->orderBy($this->getTableAlias().'.dateAdded', \Doctrine\Common\Collections\Order::Descending->value);
```

### Creating AJAX requests in functional tests
```diff
- $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
+ $this->setCsrfHeader(); // this is necessary only for the /s/ajax endpoints. Other ajax requests do not need it.
+ $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax', $payload);
```

### Logging in different user in functional tests
```diff
- $user = $this->loginUser('admin');
+ $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
+ $this->loginUser($user);
```

### Asserting successful response in functional tests
```diff
$this->client->request('GET', '/s/campaigns/new/');
- $response = $this->client->getResponse();
- Assert::assertTrue($response->isOk(), $response->getContent());
+ $this->assertResponseIsSuccessful();
```

### Session service doesn't exist anymore
Use Request to get the session instead.
```diff
- use Symfony\Component\HttpFoundation\Session\SessionInterface;
+ use Symfony\Component\HttpFoundation\RequestStack;
class NeedsSession
{
-   public function __construct(private SessionInterface $session) {}
+   public function __construct(private RequestStack $requestStack) {}

    public function doStuff()
    {
-       $selected = $this->session->get('mautic.category.type', 'category');
+       $selected = $this->requestStack->getSession()->get('mautic.category.type', 'category');
        // ...
    }
}
```
