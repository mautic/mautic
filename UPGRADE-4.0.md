# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from 7.3 to 7.4.
    *   Minimal MySQL version was increased from x to x
*   Symfony 4
    *   Symfony deprecations were removed or refactored [https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md](https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md)
    *   Services are now private by default in Symfony 4. Mautic has a "hack" to register its own services as public but dependency injection should be preferred for Commands, Controllers, and services. Some Symfony services may no longer be available to the Controller via the Container.
    *   \Mautic\CoreBundle\Form\Type\YesNoButtonGroupType now uses false/true values which Symfony 4 will convert to empty values for No in the UI. This shouldn't cause issues for most unless the field is using a NotBlank constraint, which is no longer valid, or submitting a form via a functional test with 0 as the value of a YesNoButtonGroupType field. 
*   Packages removed
    *   debril/rss-atom-bundle removed
    *   egeloen/ordered-form-bundle removed
    *   sensio/distribution-bundle removed
    *   codeception/codeception removed
    *   joomla/http removed (see note on `mautic.http.connector` below)
    *   ricardofiorani/guzzle-psr18-adapter removed
    *   codeception/codeception removed
*   Packages updated
    *   (all symfony/* packages)
    *   doctrine/doctrine-bundle to ^2.1.1
    *   doctrine/doctrine-fixtures-bundle to ^3.3.2
    *   doctrine/annotations to ^1.10.3
    *   doctrine/orm to ^2.8.0
    *   friendsofsymfony/rest-bundle to ^3.0.2
    *   friendsofsymfony/oauth-server-bundle to [dev-doctrine-fix](https://github.com/FriendsOfSymfony/FOSOAuthServerBundle/compare/1.6.2...dennisameling:doctrine-fix?expand=1)
    *   jms/serializer-bundle to ^3.8.0
    *   oneup/uploader-bundle to ^3.1.0
    *   php-amqplib/rabbitmq-bundle to ^2.5.1
    *   knplabs/knp-menu-bundle ^3.0
    *   helios-ag/fm-elfinder-bundle to ^10.1
    *   tightenco/collect to ^8.16.0
    *   theofidry/psysh-bundle to ~4.4.0
    *   phpstan/phpstan to ^0.12.82
*   Commands
    * \Mautic\CoreBundle\Command\ModeratedCommand::$lockHandler is now private
*   Services
    * `mautic.http.client` has been upgraded from GuzzleHttp 6 to 7. You can now leverage `Psr\Http\Client\ClientInterface` (PSR-18) or `GuzzleHttp\Client` (more convenience methods) in your class constructors! Example:

    config.php:

    ```PHP
    'mautic.demo.class' => [
        'class'     => DemoClass::class,
        'arguments' => [
            'mautic.http.client',
        ],
    ],
    ```

    DemoClass.php

    ```PHP
    use Psr\Http\Client\ClientInterface;
    
    ....
    public function __construct(
        ClientInterface $client
    ) {
        $this->client = $client;
    }
    ```

    * `mautic.http.connector` has been removed in favor of `mautic.http.client`. See the example above on how to use it in your class constructors.

*   PluginBundle
    * If you extend `AbstractIntegration` and use the method `makeRequest`, including `$options['return_raw']`, you will now get `\Psr\Http\Message\ResponseInterface` as the response type (was `\Joomla\CMS\Http\Response`)
    * If you're listening on the `Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE` event, `PluginIntegrationRequestEvent->getResponse()` now returns `\Psr\Http\Message\ResponseInterface` as the type (was not explicitly defined)

*   WebhookBundle
    * \Mautic\WebhookBundle\Entity\Webhook::getQueues() removed and there is no replacement
    * \Mautic\WebhookBundle\Entity\Webhook::addQueues() removed and there is no replacement
    * \Mautic\WebhookBundle\Entity\Webhook::addQueue() removed and there is no replacement
    * \Mautic\WebhookBundle\Entity\Webhook::removeQueue() removed and there is no replacement

    