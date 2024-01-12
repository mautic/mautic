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
        'Form/DataTransformer/DsnTransformer.php',
    ];

    $services->load('Mautic\\ConfigBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->get(Mautic\ConfigBundle\Form\Type\EscapeTransformer::class)->arg('$allowedParameters', '%mautic.config_allowed_parameters%');
    $services->get(Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class)->arg('$restrictedFields', '%mautic.security.restrictedConfigFields%');
    $services->get(Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class)->arg('$displayMode', '%mautic.security.restrictedConfigFields.displayMode%');

    // @deprecated Remove all aliases in Mautic 6. Use FQCN instead.
    $services->alias('mautic.config.model.sysinfo', Mautic\ConfigBundle\Model\SysinfoModel::class);
    $services->alias('mautic.config.mapper', Mautic\ConfigBundle\Mapper\ConfigMapper::class);
    $services->alias('mautic.config.config_change_logger', Mautic\ConfigBundle\Service\ConfigChangeLogger::class);
    $services->alias('mautic.config.form.escape_transformer', Mautic\ConfigBundle\Form\Type\EscapeTransformer::class);
    $services->alias('mautic.config.form.restriction_helper', Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class);
};
