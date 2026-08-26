<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\UserBundle\EventListener\ApiUserSubscriber;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Mautic\UserBundle\Security\Authenticator\PluginAuthenticator;
use Mautic\UserBundle\Security\Authenticator\SsoAuthenticator;
use Mautic\UserBundle\Security\EntryPoint\MainEntryPoint;
use Mautic\UserBundle\Security\Provider\UserProvider;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('Mautic\\UserBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\UserBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set(Mautic\UserBundle\ApiPlatform\UserProcessor::class)
        ->args([
            service('api_platform.doctrine.orm.state.persist_processor'),
            service('security.user_password_hasher'),
        ])
        ->tag('api_platform.state_processor');

    $services->set('security.authenticator.mautic_sso', SsoAuthenticator::class)
        ->abstract()
        ->args([
            '$httpUtils'      => service('security.http_utils'),
            '$userProvider'   => abstract_arg('user provider'),
            '$successHandler' => abstract_arg('authentication success handler'),
            '$failureHandler' => abstract_arg('authentication failure handler'),
            '$options'        => abstract_arg('options'),
        ]);

    $services->set('security.authenticator.mautic_api', PluginAuthenticator::class)
        ->abstract()
        ->args([
            '$oAuth2' => service('fos_oauth_server.server'),
        ]);

    $services->set(Mautic\UserBundle\Security\SAML\Helper::class);
    $services->set(TokenPermissions::class);

    $services->set(UserProvider::class);

    $services->load('Mautic\\UserBundle\\Security\\EntryPoint\\', '../Security/EntryPoint/*.php');
    $services->load('Mautic\\UserBundle\\Security\\Authentication\\Token\\Permissions\\', '../Security/Authentication/Token/Permissions/*.php');

    $services->alias(Mautic\UserBundle\Entity\UserTokenRepositoryInterface::class, Mautic\UserBundle\Entity\UserTokenRepository::class);

    $services->alias('mautic.user.model.password_strength_estimator', Mautic\UserBundle\Model\PasswordStrengthEstimatorModel::class);

    $services->load('Mautic\\UserBundle\\Security\\SAML\Store\\Request\\', '../Security/SAML/Store/Request/*.php');
    $services->get(Mautic\UserBundle\Security\SAML\Store\Request\RequestStateStore::class)
        ->arg('$prefix', '%lightsaml.store.request_session_prefix%')
        ->arg('$suffix', '%lightsaml.store.request_session_sufix%');
    $services->get(MainEntryPoint::class)->arg('$samlEnabled', '%env(MAUTIC_SAML_ENABLED)%');
    $services->get(ApiUserSubscriber::class)->arg('$userProvider', service('security.user_providers'));

    // Below are fixes for autowiring of SAML SpBundle.
    $services->alias(LightSaml\SymfonyBridgeBundle\Bridge\Container\BuildContainer::class, 'lightsaml.container.build');
    $services->load('LightSaml\\SpBundle\\Controller\\', '%kernel.project_dir%/vendor/javer/sp-bundle/src/LightSaml/SpBundle/Controller/*.php')
        ->tag('controller.service_arguments');

    $services->set(Mautic\UserBundle\Security\SAML\User\UserMapper::class)
        ->arg('$attributes', ['email' => param('mautic.saml_idp_email_attribute'), 'username' => param('mautic.saml_idp_username_attribute'), 'firstname' => param('mautic.saml_idp_firstname_attribute'), 'lastname' => param('mautic.saml_idp_lastname_attribute')]);
    $services->set(Doctrine\ORM\EntityManager::class)
        ->factory([service('doctrine'), 'getManagerForClass'])
        ->args([Mautic\UserBundle\Entity\User::class]);
    $services->set(Doctrine\ORM\EntityManager::class)
        ->factory([service('doctrine'), 'getManagerForClass'])
        ->args([Mautic\UserBundle\Entity\Permission::class]);
    $services->set(LightSaml\Builder\EntityDescriptor\SimpleEntityDescriptorBuilder::class)
        ->factory(Mautic\UserBundle\Security\SAML\EntityDescriptorProviderFactory::build(...))
        ->args([param('lightsaml.own.entity_id'), service('router'), param('lightsaml.route.login_check'), service('lightsaml.own.credential_store')]);
    $services->set(Mautic\UserBundle\Security\SAML\Store\CredentialsStore::class)
        ->arg('$entityId', param('mautic.saml_idp_entity_id'))
        ->tag('lightsaml.own_credential_store');
    $services->set(Mautic\UserBundle\Security\SAML\Store\TrustOptionsStore::class)
        ->arg('$entityId', param('mautic.saml_idp_entity_id'))
        ->tag('lightsaml.trust_options_store');
    $services->set('mautic.security.saml.user_creator', Mautic\UserBundle\Security\SAML\User\UserCreator::class)
        ->arg('$defaultRole', param('mautic.saml_idp_default_role'));

    $services->set(Mautic\UserBundle\Security\Authentication\AuthenticationHandler::class);

    $services->set(Mautic\UserBundle\Security\SAML\Store\EntityDescriptorStore::class)->tag('lightsaml.idp_entity_store');

    $services->set('mautic.security.saml.id_store', Mautic\UserBundle\Security\SAML\Store\IdStore::class);

    $services->set(Mautic\UserBundle\Security\UserTokenSetter::class);

    $services->set('mautic.user.model.user_token_service', Mautic\UserBundle\Model\UserToken\UserTokenService::class);
    // Decorate the form_login class to ensure no user enumeration can
    // happen via timing attacks.
    $services->set('mautic.security.authenticator.form_login.decorator', Mautic\UserBundle\Security\TimingSafeFormLoginAuthenticator::class)
        ->decorate('security.authenticator.form_login.main')
        ->args([
            service('.inner'),
            service(UserProvider::class),
            service('security.password_hasher_factory'),
            [], // This will be replaced by the compiler pass
        ]);
    $services->set(Mautic\UserBundle\Security\Permissions\UserPermissions::class);
};
