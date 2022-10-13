<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

// This is loaded by \Mautic\CoreBundle\DependencyInjection\MauticCoreExtension to auto-wire Commands
// as they were done in M3 which must be done when the bundle config.php's services are processed to prevent
// Symfony attempting to auto-wire commands manually registered by bundle

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure() // Automatically registers services as commands as was in M3
        ->public() // Set as public as was the default in M3
    ;

    $excludeBundleDirectories = [
        'DependencyInjection',
        'Entity',
        'Config',
        'Test',
        'Tests',
        'Views',
        'EventCollector',
        'Event',
        'Exception',
        'Crate',
        'DataObject',
        'DTO',
        'OptionsAccessor',
        'Migrations',
        'Migration',
        'Generator',
        'Doctrine',
        'Model/IteratorExportDataModel.php',
        'Model/ConsoleOutputModel.php',
        'Form/EventListener/FormExitSubscriber.php',
        'Form/DataTransformer',
        'Security',
        'Release',
        'Serializer/Driver',
        'Serializer/Exclusion',
        'Controller',
        'Executioner/ContactFinder/Limiter/ContactLimiter.php',
        'Executioner/Dispatcher/Exception',
        'Executioner/Scheduler/Mode/DAO',
        'Membership/Exception',
        'PreferenceBuilder/ChannelPreferences.php',
        'PreferenceBuilder/PreferenceBuilder.php',
        'Helper/Chart',
        'Helper/CommandResponse.php',
        'Helper/Language/Installer.php',
        'Helper/PageHelper.php',
        'Helper/Tree/IntNode.php',
        'Helper/Update/Github/Release.php',
        'Helper/Update/PreUpdateChecks',
        'Helper/SchemaHelper.php',
        'Helper/FieldFilterHelper.php',
        'Helper/FieldMergerHelper.php',
        'Helper/oAuthHelper.php',
        'Helper/RabbitMqProducer.php',
        'Session/Storage/Handler/RedisSentinelSessionHandler.php',
        'Templating/Engine/PhpEngine.php', // Will be removed in M5
        'Templating/Helper/FormHelper.php',
        'Templating/Helper/ThemeHelper.php',
        'Translation/TranslatorLoader.php',
        'MonitoredEmail/Accessor',
        'MonitoredEmail/Organizer',
        'MonitoredEmail/Processor',
        'Stat/Reference.php',
        'Swiftmailer', // Will be removed in M5
        'ProgressiveProfiling/DisplayCounter.php',
        'ProgressiveProfiling/DisplayManager.php',
        'Auth/Support/Oauth2/Token',
        'Sync/DAO',
        'Sync/Exception',
        'Sync/SyncProcess/SyncProcess.php',
        'Deduplicate/Exception',
        'Field/DTO',
        'Field/Event',
        'Segment/ContactSegmentFilter.php',
        'Segment/ContactSegmentFilterCrate.php',
        'Segment/Decorator',
        'Segment/DoNotContact',
        'Segment/IntegrationCampaign',
        'Segment/Query',
        'Segment/Stat',
        'Integration/IntegrationObject.php',
        'MauticQueueBundle.php',
        'Builder/MauticReportBuilder.php',
        'Scheduler/Entity',
        'Scheduler/Option',
        'Aggregate/Collection',
        'Aggregate/Calculator.php',
    ];

    // Auto-register Commands as it worked in M3
    $services->load('Mautic\\', '../bundles/')->exclude('../bundles/*/{'.implode(',', $excludeBundleDirectories).'}');
    $services->load('MauticPlugin\\', '../../plugins/*/Command/*');
};
