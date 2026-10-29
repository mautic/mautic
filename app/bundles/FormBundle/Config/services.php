<?php

declare(strict_types=1);

use Mautic\FormBundle\Event\Service\FieldValueTransformer;
use Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator;
use Mautic\FormBundle\Security\Permissions\FormPermissions;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'ProgressiveProfiling/DisplayCounter.php',
        'ProgressiveProfiling/DisplayManager.php',
    ];

    $services->set(FieldValueTransformer::class);

    $services->load('Mautic\\FormBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\FormBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(FileExtensionConstraintValidator::class)
        ->tag('validator.constraint_validator', ['alias' => 'file_extension_constraint_validator']);

    $services->set(FormPermissions::class);
};
