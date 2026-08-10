# Backwards compatibility breaking changes

## Platform requirements
- The minimum required PHP version has been increased from **8.2** to **8.4**.

## Removed code

- Deprecated method `Mautic\LeadBundle\Model\LeadModel::isContactable()` removed. Use `Mautic\LeadBundle\Model\DoNotContact::isContactable()` instead.

## Changed code

- All permission classes are now registered as services instead of being instantiated on the fly by `Mautic\CoreBundle\Security\Permissions\CorePermissions`. `Mautic\CoreBundle\Security\Permissions\AbstractPermissions` takes `Mautic\CoreBundle\Helper\CoreParametersHelper` in the constructor instead of an array of parameters, so permission classes need no constructor at all. Define the permissions in `definePermissions()` instead of the constructor:

    ```diff
    -public function __construct(array $params)
    -{
    -    parent::__construct($params);
    -    $this->addStandardPermissions('categories');
    -}
    +public function definePermissions(): void
    +{
    +    $this->addStandardPermissions('categories');
    +}
    ```

    Third party permission classes must be registered in the bundle's `Config/services.php`. The `mautic.permissions` tag is added automatically to every `AbstractPermissions` child registered with autoconfiguration enabled:

    ```php
    $services->set(MauticPlugin\AcmeBundle\Security\Permissions\AcmePermissions::class);
    ```
