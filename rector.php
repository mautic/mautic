<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;

return static function (Rector\Config\RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__.'/app/bundles', __DIR__.'/plugins']);
    $rectorConfig->skip(
        [
            __DIR__.'/*/test/*',
            __DIR__.'/*/tests/*',
            __DIR__.'/*/Test/*',
            __DIR__.'/*/Tests/*',
            __DIR__.'/*.html.php',
            __DIR__.'/*.less.php',
            __DIR__.'/*.inc.php',
            __DIR__.'/*.js.php',
            \Rector\Symfony\Rector\MethodCall\ContainerGetToConstructorInjectionRector::class => [
                __DIR__.'/app/bundles/AssetBundle/Controller/UploadController.php', // This is just overrride of the DropzoneController.
                __DIR__.'/app/bundles/CoreBundle/Factory/MauticFactory.php', // Requires quite a refactoring.
                __DIR__.'/plugins/MauticCitrixBundle/MauticCitrixBundle.php', // Requires quite a refactoring.
                __DIR__.'/app/bundles/CoreBundle/Helper/TemplatingHelper.php', // Will be removed once Twig refactoring is done.
            ],
        ]
    );

    $rectorConfig->parallel();

    foreach (['dev', 'test', 'prod'] as $environment) {
        $environmentCap = ucfirst($environment);
        $xmlPath        = __DIR__."/var/cache/{$environment}/appAppKernel{$environmentCap}DebugContainer.xml";
        if (file_exists($xmlPath)) {
            $rectorConfig->symfonyContainerXml($xmlPath);
            break;
        }
    }

    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->cacheDirectory(__DIR__.'/var/cache/rector');

    // Define what rule sets will be applied
    $rectorConfig->sets([
        \Rector\Symfony\Set\SymfonyLevelSetList::UP_TO_SYMFONY_44,
        \Rector\Doctrine\Set\DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_BEHAVIORS_20,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_CODE_QUALITY,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_COMMON_20,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_210,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_211,
        //\Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_30, this rule should run after the upgrade to doctrine 3.0
        //\Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_40, this rule should run after the upgrade to doctrine 4.0
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_213,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_214,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_29,
        //\Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_REPOSITORY_AS_SERVICE, will break code in Mautic, needs to be fixed first
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_25,

        // @todo implement the whole set. Start rule by rule below.
        // \Rector\Set\ValueObject\SetList::DEAD_CODE
    ]);

    // Define what single rules will be applied
    $rectorConfig->rule(\Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\For_\RemoveDeadContinueRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector::class);
    $rectorConfig->rule(\Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class);
    $rectorConfig->rule(\Rector\Symfony\Rector\MethodCall\ContainerGetToConstructorInjectionRector::class);

    $rectorConfig->ruleWithConfiguration(\Rector\Doctrine\Rector\MethodCall\EntityAliasToClassConstantReferenceRector::class, [
        \Rector\Doctrine\Rector\MethodCall\EntityAliasToClassConstantReferenceRector::ALIASES_TO_NAMESPACES         => [
            'MauticApiBundle'                         => 'Mautic\ApiBundle\Entity',
            'MauticAssetBundle'                       => 'Mautic\AssetBundle\Entity',
            'MauticCampaignBundle'                    => 'Mautic\CampaignBundle\Entity',
            'MauticCategoryBundle'                    => 'Mautic\CategoryBundle\Entity',
            'MauticChannelBundle'                     => 'Mautic\ChannelBundle\Entity',
            'MauticCoreBundle'                        => 'Mautic\CoreBundle\Entity',
            'MauticDashboardBundle'                   => 'Mautic\DashboardBundle\Entity',
            'MauticDynamicContentBundle'              => 'Mautic\DynamicContentBundle\Entity',
            'MauticEmailBundle'                       => 'Mautic\EmailBundle\Entity',
            'MauticFormBundle'                        => 'Mautic\FormBundle\Entity',
            'MauticIntegrationBundle'                 => 'Mautic\IntegrationBundle\Entity',
            'MauticLeadBundle'                        => 'Mautic\LeadBundle\Entity',
            'MauticNotificationBundle'                => 'Mautic\NotificationBundle\Entity',
            'MauticPageBundle'                        => 'Mautic\PageBundle\Entity',
            'MauticPluginBundle'                      => 'Mautic\PluginBundle\Entity',
            'MauticPointBundle'                       => 'Mautic\PointBundle\Entity',
            'MauticReportBundle'                      => 'Mautic\ReportBundle\Entity',
            'MauticSmsBundle'                         => 'Mautic\SmsBundle\Entity',
            'MauticStageBundle'                       => 'Mautic\StageBundle\Entity',
            'MauticUserBundle'                        => 'Mautic\UserBundle\Entity',
            'MauticWebhookBundle'                     => 'Mautic\WebhookBundle\Entity',
            'MauticPluginMauticSocialBundle'          => 'MauticPlugin\MauticSocialBundle\Entity',
            'MauticPluginMauticCitrixBundle'          => 'MauticPlugin\MauticCitrixBundle\Entity',
            'MauticPluginMauticCrmBundle'             => 'MauticPlugin\MauticCrmBundle\Entity',
            'MauticPluginMauticTagManagerBundle'      => 'MauticPlugin\MauticTagManagerBundle\Entity',
            'MauticPluginMauticFocusBundle'           => 'MauticPlugin\MauticFocusBundle\Entity',
            'MauticPluginMauticGrapesJsBuilderBundle' => 'MauticPlugin\MauticGrapesJsBuilderBundle\Entity',
            'FOSOAuthServerBundle'                    => 'FOS\OAuthServerBundle\Entity',
        ],
    ]);
};
