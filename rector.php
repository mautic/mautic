<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
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
        Utils\Rector\AddExpectsOnceToMockMethodCallRector::class,

        // DI
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class,
    ])
    ->reportUnusedSkips()
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        // handle later
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsForDataProviderRector::class,

        // @todo move to "twig" group
        Rector\Php84\Rector\Class_\DeprecatedAnnotationToDeprecatedAttributeRector::class,

        // handle next
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsWithoutExpectationsAttributeRector::class,

        // applied in a follow-up pull request
        Utils\Rector\AddExpectsOnceToMockMethodCallRector::class,

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

        Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class => [
            // test fixture
            __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Doctrine/ArrayTypeTest.php',
        ],
    ])
    ->reportUnusedSkips();
