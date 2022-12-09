<?php

declare(strict_types=1);

use Mautic\ConfigBundle\Controller\SysinfoController;
use Mautic\ConfigBundle\Model\SysinfoModel;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('Mautic\\ConfigBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->get(\Mautic\ConfigBundle\Form\Type\EscapeTransformer::class)->arg('$allowedParameters', '%mautic.config_allowed_parameters%');
    $services->get(\Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class)->arg('$restrictedFields', '%mautic.security.restrictedConfigFields%');
    $services->get(\Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class)->arg('$mode', '%mautic.security.restrictedConfigFields.displayMode%');

    // @deprecated Remove all aliases in Mautic 6. Use FQCN instead.
    $services->alias('mautic.config.model.sysinfo', \Mautic\ConfigBundle\Model\SysinfoModel::class);
    $services->alias('mautic.config.mapper', \Mautic\ConfigBundle\Mapper\ConfigMapper::class);
    $services->alias('mautic.config.config_change_logger', \Mautic\ConfigBundle\Service\ConfigChangeLogger::class);
    $services->alias('mautic.config.form.escape_transformer', \Mautic\ConfigBundle\Form\Type\EscapeTransformer::class);
    $services->alias('mautic.config.form.restriction_helper', \Mautic\ConfigBundle\Form\Helper\RestrictionHelper::class);

    $services->set(SysinfoController::class)
        ->arg('$sysinfoModel', ref(SysinfoModel::class))
        ->call('setContainer', [ref('service_container')]);
};
