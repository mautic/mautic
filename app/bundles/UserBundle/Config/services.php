<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\UserBundle\EventListener\ApiUserSubscriber;
use Mautic\UserBundle\Security\Authentication\Token\Permissions\TokenPermissions;
use Mautic\UserBundle\Security\Authenticator\PluginAuthenticator;
use Mautic\UserBundle\Security\Authenticator\SsoAuthenticator;
use Mautic\UserBundle\Security\EntryPoint\MainEntryPoint;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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

    $services->set('security.token.permissions', TokenPermissions::class);

    $services->load('Mautic\\UserBundle\\Security\\EntryPoint\\', '../Security/EntryPoint/*.php');
    $services->load('Mautic\\UserBundle\\Security\\Authentication\\Token\\Permissions\\', '../Security/Authentication/Token/Permissions/*.php');

    $services->alias(Mautic\UserBundle\Entity\UserTokenRepositoryInterface::class, Mautic\UserBundle\Entity\UserTokenRepository::class);

    $services->alias('mautic.user.model.role', Mautic\UserBundle\Model\RoleModel::class);
    $services->alias('mautic.user.model.user', Mautic\UserBundle\Model\UserModel::class);
    $services->alias('mautic.user.repository.user_token', Mautic\UserBundle\Entity\UserTokenRepository::class);
    $services->alias('mautic.user.repository', Mautic\UserBundle\Entity\UserRepository::class);
    $services->alias('mautic.permission.repository', Mautic\UserBundle\Entity\PermissionRepository::class);
    $services->alias('mautic.user.model.password_strength_estimator', Mautic\UserBundle\Model\PasswordStrengthEstimatorModel::class);
    $services->get(Mautic\UserBundle\Form\Validator\Constraints\NotWeakValidator::class)->tag('validator.constraint_validator');

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
    // Decorate the form_login class to ensure no user enumeration can
    // happen via timing attacks.
    $services->set('mautic.security.authenticator.form_login.decorator', Mautic\UserBundle\Security\TimingSafeFormLoginAuthenticator::class)
        ->decorate('security.authenticator.form_login.main')
        ->args([
            service('.inner'),
            service('mautic.user.provider'),
            service('security.password_hasher_factory'),
            [], // This will be replaced by the compiler pass
        ]);
};
