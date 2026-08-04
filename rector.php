<?php

declare(strict_types=1);

use Mautic\CoreBundle\Entity\CommonRepository;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Utils\Rector\UnserializeToSerializerDecodeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        phpunitCodeQuality: true,
        phpunitMockToStub: true,
        phpunitNarrowAsserts: true,
        privatization: true,
    )
    ->withPhpSets()
    ->withCache(__DIR__.'/var/cache/rector')
    ->withTypeGuardedClasses([
        // common controllers
        Mautic\CoreBundle\Controller\AbstractStandardFormController::class,
        Mautic\CoreBundle\Controller\CommonController::class,
        Mautic\CoreBundle\Controller\AbstractFormController::class,
        Mautic\ApiBundle\Controller\CommonApiController::class,
        Mautic\ApiBundle\Controller\FetchCommonApiController::class,
        Mautic\PluginBundle\Integration\AbstractIntegration::class,
        Mautic\LeadBundle\Controller\Api\CustomFieldsApiControllerTrait::class,
        // other objects
        CommonRepository::class,
        Mautic\CoreBundle\Security\Permissions\AbstractPermissions::class,
        MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::class,
        Mautic\PluginBundle\Integration\AbstractIntegration::class,
    ])
    ->withRules([
        Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector::class,

        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AssertClassToThisAssertRector::class,
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,

        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        UnserializeToSerializerDecodeRector::class,

        // symfony
        Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector::class,

        // DI
        // ModelGetRepositoryToRepositoryServiceRector::class,
    ])
    ->reportUnusedSkips()
    ->withCodeQualityLevel(45)
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        // to be deprecated as depends on personal preference
        Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector::class,

        // opinionated
        Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector::class,

        // test fixtures
        __DIR__.'/plugins/*/node_modules/*',
        __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Helper/resource/',

        UnserializeToSerializerDecodeRector::class => [
            // tests
            __DIR__.'/app/bundles/UserBundle/Tests/Entity/UserTest.php',
        ],

        // streamed response above
        Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector::class => [
            __DIR__.'/app/bundles/ReportBundle/Model/ReportModel.php',
        ],

        Rector\DeadCode\Rector\Property\RemoveDefaultValueFromAssignedPropertyRector::class => [
            // buggy
            __DIR__.'/plugins/MauticCrmBundle/Integration/Salesforce/CampaignMember/Fetcher.php',
        ],

        Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector::class => [
            __DIR__.'/app/bundles/PageBundle/Controller/AjaxController.php',
            __DIR__.'/app/bundles/EmailBundle/Controller/AjaxController.php',
        ],

        // fixed in dev-main
        Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class => [
            __DIR__.'/app/bundles/LeadBundle/Model/LeadModel.php',
        ],

        // modified with reflection
        Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Entity/EmailDraft.php',
            __DIR__.'/app/bundles/EmailBundle/Helper/MailHelper.php',
            __DIR__.'/app/bundles/CoreBundle/Twig/Helper/DateHelper.php',
        ],

        // Avoiding breaking BC breaks with forced return types in public methods
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Integration/ObjectChangeGenerator.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Internal/ObjectChangeGenerator.php',
        ],

        Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class => [
            // test fixture
            __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Doctrine/ArrayTypeTest.php',
        ],
    ])
    ->reportUnusedSkips();
