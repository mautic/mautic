# Backwards compatibility breaking changes

## Platform requirements
- The minimum required PHP version has been increased from **8.1** to **8.2**.
- The minimum required MySQL version has been increased from **5.7.14** to **8.4.0**.
- The minimum required MariaDB version has been increased from **10.2.7** to **10.11.0**.

## Removed features
- The ability to update Mautic in the browser (via user interface) has been removed. To update Mautic, use the **command line** instead.
- The API Limiter was removed temporarily. Shoud be added back before the M7-stable is released. https://github.com/mautic/mautic/pull/14876
- The `track_by_tracking_url` configuration setting has been removed. Identification of contacts via the `ct` parameter in tracking URLs was already the default behavior.

## Removed code

- Deprecated class `Mautic\MessengerBundle\MessageHandler\TestHandler` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Factory\MauticFactory` removed. Use dependency injection instead.
- Deprecated class `Mautic\CampaignBundle\Service\Campaign` removed with no replacement.
- Deprecated class `Mautic\CampaignBundle\Form\Type\CampaignEventLeadChangeType` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Event\CustomFormEvent` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Helper\UTF8Helper` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Test\MauticSqliteTestCase` removed with no replacement.
- Deprecated class `Mautic\CoreBundle\Twig\Extension\EmojiExtension` removed with no replacement.
- Deprecated class `Mautic\EmailBundle\Form\Type\AbTestPropertiesType` removed with no replacement.
- Deprecated class `Mautic\EmailBundle\Helper\RequestStorageHelper` removed with no replacement.
- Deprecated class `Mautic\FormBundle\Tests\FormTestAbstract` removed with no replacement.
- Deprecated class `Mautic\LeadBundle\Helper\PointEventHelper` removed with no replacement.
- Deprecated class `Mautic\MarketplaceBundle\Exception\InstallException` removed with no replacement.
- Deprecated class `Mautic\NotificationBundle\Event\NotificationClickEvent` removed with no replacement.
- Deprecated class `Mautic\NotificationBundle\Event\SendingNotificationEvent` removed with no replacement.
- Deprecated class `Mautic\NotificationBundle\Form\Type\ConfigType` removed with no replacement.
- Deprecated class `Mautic\SmsBundle\Api\AbstractSmsApi` removed with no replacement.
- Deprecated class `Mautic\SmsBundle\Api\TwilioApi` removed with no replacement.
- Deprecated interface `Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\AuthorizatorInterface` removed. Use `Mautic\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface` instead.
- Deprecated interface `Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\CredentialsInterface` removed. Use `Mautic\IntegrationsBundle\Auth\Provider\AuthCredentialsInterface` instead.
- Deprecated class `Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\AbstractClientFactory` removed with no replacement.
- Deprecated trait `Mautic\IntegrationsBundle\Integration\BC\BcIntegrationSettingsTrait` removed with no replacement.
- Deprecated exception classes `Mautic\CampaignBundle\Executioner\Scheduler\Exception\ExecutionProhibitedException` and `NotTimeYetException` removed with no replacement.
- Deprecated form data transformers removed:
  - `Mautic\CoreBundle\Form\DataTransformer\DatetimeToStringTransformer`
  - `Mautic\CoreBundle\Form\DataTransformer\EmojiToHtmlTransformer`  
  - `Mautic\CoreBundle\Form\DataTransformer\NullToEmptyTransformer`
- Deprecated plugin classes removed:
  - `MauticFullContactBundle\Services\FullContact_API`
  - `MauticSocialBundle\Form\Type\TwitterCustomType`
- File `app/AppCache.php` removed as it was no longer used.

## Removed Composer dependencies
- `symfony/yaml` see https://github.com/mautic/mautic/pull/14850
- `symfony/security-acl` see https://github.com/mautic/mautic/pull/14850
- `symfony/css-selector` see https://github.com/mautic/mautic/pull/14850
- `symfony/templating` see https://github.com/mautic/mautic/pull/14850
- `noxlogic/ratelimit-bundle` see https://github.com/mautic/mautic/pull/14876
- `symfony/amqp-messenger` see https://github.com/mautic/mautic/pull/14883
- `php-amqplib/rabbitmq-bundle` see https://github.com/mautic/mautic/pull/14883
- `bandwidth-throttle/token-bucket` see https://github.com/mautic/mautic/pull/15271

## BC breaks in the code

### PHP
- Removed `Mautic\DashboardBundle\Dashboard\Widget::FORMAT_MYSQL` constant. Use `DateTimeHelper::FORMAT_DB_DATE_ONLY` instead.
- Removed `Mautic\ApiBundle\Security\OAuth2\Firewall::OAuthListener` class as it was empty. Use `FOS\OAuthServerBundle\Security\Firewall\OAuthListener` instead.
- Removed `Mautic\LeadBundle\Segment\Query\Filter\SegmentReferenceFilterQueryBuilder` as unused.

### Javascript
- Removed `Mautic.insertTextInEditor` function. Use `Mautic.insertHtmlInEditor` instead.
