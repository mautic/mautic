# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from x to x.
    *   Minimal MySQL version was increased from x to x
*   Symfony 4
    *   Symfony deprecations were removed or refactored [https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md](https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md)
    *   Services are now private by default in Symfony 4. Mautic has a "hack" to register its own services as public but dependency injection should be preferred for Commands, Controllers, and services. Some Symfony services may no longer be available to the Controller via the Container.
*   Packages removed
    *   debril/rss-atom-bundle removed
    *   egeloen/ordered-form-bundle removed
    *   sensio/distribution-bundle removed
    *   codeception/codeception removed
    *   joomla/http removed
    *   TODO add additional packages here that were removed
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

*   Plugins
    * If you extend `AbstractIntegration` and use the method `makeRequest`, including `$options['return_raw']`, you will now get `\Psr\Http\Message\ResponseInterface` as the response type (was `\Joomla\CMS\Http\Response`)
    * If you're listening on the `Mautic\PluginBundle\PluginEvents::PLUGIN_ON_INTEGRATION_RESPONSE` event, `PluginIntegrationRequestEvent->getResponse()` now returns `\Psr\Http\Message\ResponseInterface` as the type (was not explicitly defined)
