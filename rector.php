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
    ->withPhpSets(php84: true)
    ->withCache(__DIR__.'/var/cache/rector')
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
        // handle later
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsForDataProviderRector::class,

        // this would escalate to runtime report, not what we want
        Rector\Php84\Rector\Class_\DeprecatedAnnotationToDeprecatedAttributeRector::class,

        // @todo move to "twig" group
        Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector::class,
        Rector\Symfony\Symfony73\Rector\Class_\GetFunctionsToAsTwigFunctionAttributeRector::class,

        Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector::class,
        Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector::class,
        Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector::class,

        // handle next
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsWithoutExpectationsAttributeRector::class,

        Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector::class,
        Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector::class,
        Rector\Symfony\CodeQuality\Rector\Class_\LoadValidatorMetadataToAttributeRector::class,
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class => [
            __DIR__.'/app/bundles/PageBundle/Form/Type/PreferenceCenterListType.php',
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
