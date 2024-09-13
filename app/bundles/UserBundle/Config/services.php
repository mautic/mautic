<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

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

    $services->alias(Mautic\UserBundle\Entity\UserTokenRepositoryInterface::class, Mautic\UserBundle\Entity\UserTokenRepository::class);

    $services->alias('mautic.user.model.role', Mautic\UserBundle\Model\RoleModel::class);
    $services->alias('mautic.user.model.user', Mautic\UserBundle\Model\UserModel::class);
    $services->alias('mautic.user.repository.user_token', Mautic\UserBundle\Entity\UserTokenRepository::class);
    $services->alias('mautic.user.repository', Mautic\UserBundle\Entity\UserRepository::class);
    $services->alias('mautic.permission.repository', Mautic\UserBundle\Entity\PermissionRepository::class);
    $services->get(Mautic\UserBundle\Form\Validator\Constraints\NotWeakValidator::class)->tag('validator.constraint_validator');
};
