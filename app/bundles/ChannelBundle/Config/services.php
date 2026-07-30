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
        'PreferenceBuilder/ChannelPreferences.php',
        'PreferenceBuilder/PreferenceBuilder.php',
    ];

    $services->load('Mautic\\ChannelBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ChannelBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->alias('mautic.channel.model.message', Mautic\ChannelBundle\Model\MessageModel::class);
    $services->alias('mautic.channel.model.queue', Mautic\ChannelBundle\Model\MessageQueueModel::class);

    $services->set(Mautic\ChannelBundle\Helper\ChannelListHelper::class)
        ->tag('twig.helper', ['alias' => 'channel']);
};
