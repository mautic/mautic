# Backwards compatibility breaking changes

## Platform requirements
- The minimum required PHP version has been increased from **8.1** to **8.2**.
- The minimum required MySQL version has been increased from **5.7.14** to **8.4.0**.
- The minimum required MariaDB version has been increased from **10.2.7** to **10.11.0**.

## Removed features
- The ability to update Mautic in the browser (via user interface) has been removed. To update Mautic, use the **command line** instead.
- The API Limiter was removed temporarily. Shoud be added back before the M7-stable is released. https://github.com/mautic/mautic/pull/14876

## Removed Composer dependencies
- `symfony/yaml` see https://github.com/mautic/mautic/pull/14850
- `symfony/security-acl` see https://github.com/mautic/mautic/pull/14850
- `symfony/css-selector` see https://github.com/mautic/mautic/pull/14850
- `symfony/templating` see https://github.com/mautic/mautic/pull/14850
- `noxlogic/ratelimit-bundle` see https://github.com/mautic/mautic/pull/14876
- `symfony/amqp-messenger` see https://github.com/mautic/mautic/pull/14883
- `php-amqplib/rabbitmq-bundle` see https://github.com/mautic/mautic/pull/14883

## BC breaks in the code

### PHP
- Removed `\Mautic\DashboardBundle\Dashboard\Widget::FORMAT_MYSQL` constant. Use `DateTimeHelper::FORMAT_DB_DATE_ONLY` instead.

### Javascript
- Removed `Mautic.insertTextInEditor` function. Use `Mautic.insertHtmlInEditor` instead.
