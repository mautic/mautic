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
        codeQuality: true,
        symfonyCodeQuality: true,
        earlyReturn: true,
    )
    ->withPhpLevel(120)
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
        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AssertClassToThisAssertRector::class,
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,

        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        UnserializeToSerializerDecodeRector::class,

        // DI
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class,
    ])
    ->reportUnusedSkips()
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        PHPUnit\Metadata\DoesNotPerformAssertions::class => [
            __DIR__.'/app/bundles/CoreBundle/Tests/Twig/TwigIntegrationTestTrait.php',
        ],
        Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Tests/Twig/TwigIntegrationTestTrait.php',
        ],

        // @todo move to "twig" group
        Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector::class,
        Rector\Symfony\Symfony73\Rector\Class_\GetFunctionsToAsTwigFunctionAttributeRector::class,

        Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector::class,
        Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector::class,
        Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector::class,
        Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector::class,

        // intentional parent property assign override
        Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector::class => [
            __DIR__.'/app/bundles/ApiBundle/Entity/oAuth2/Client.php',
        ],

        // handle next
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsWithoutExpectationsAttributeRector::class,

        Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector::class,
        Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector::class,
        Rector\Symfony\CodeQuality\Rector\Class_\LoadValidatorMetadataToAttributeRector::class,
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class => [
            __DIR__.'/app/bundles/PageBundle/Form/Type/PreferenceCenterListType.php',
        ],

        Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector::class => [
            __DIR__.'/app/bundles/LeadBundle/Tests/Form/Type/FilterTypeTest.php',
        ],

        // preference to compare null over object
        Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class,

        Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class => [
            // doctrine magic
            __DIR__.'/app/bundles/CoreBundle/EventListener/DoctrineEventsSubscriber.php',
        ],

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

        Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector::class => [
            __DIR__.'/app/bundles/PageBundle/Controller/AjaxController.php',
            __DIR__.'/app/bundles/EmailBundle/Controller/AjaxController.php',
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
