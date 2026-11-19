<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\UnserializeToSerializerDecodeRector;

return RectorConfig::configure()
    ->withParallel(timeoutSeconds: 300)
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/app/tests',
        __DIR__.'/plugins',
        __DIR__.'/app/middlewares',
        // __DIR__.'/utils',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        symfonyCodeQuality: true,
        phpunitCodeQuality: true,
        phpunitMockToStub: true,
        phpunitNarrowAsserts: true,
    )
    ->withPhpSets(php84: true)
    // ->withImportNames(importShortClasses: false)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withRules([
        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AssertClassToThisAssertRector::class,
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        // custom rules
        UnserializeToSerializerDecodeRector::class,
        Utils\Rector\AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector::class,
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class,
        Utils\Rector\TestGetRepositoryToContainerGetRector::class,
        Utils\Rector\RouteRequirementParamTypeRector::class,
    ])
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        '*/Fixture/*',
        '*/Source/*',

        // SecurityController listens on kernel.request but a controller cannot expose a public non-action method;
        // DashboardSubscriber base defines listener methods that subclasses override, and method attributes do not inherit to an override
        Rector\Symfony\CodeQuality\Rector\Class_\EventSubscriberToAttributeRector::class => [
            __DIR__.'/app/bundles/UserBundle/Controller/SecurityController.php',
            __DIR__.'/app/bundles/DashboardBundle/EventListener/DashboardSubscriber.php',
        ],

        // widening the public return types to \DateTime|bool|null is out of scope for this bump
        __DIR__.'/app/bundles/CoreBundle/Helper/DateTimeHelper.php',

        // prefer implicit compare on object|null
        Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector::class,

        // removes empty #[AsEventListener] methods from anonymous listener classes used as test input
        Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class => [
            __DIR__.'/app/tests/CoreBundle/Tests/Unit/Test/AttributeEventListenerRegistrarTest.php',
        ],

        // Rector\Symfony\CodeQuality\Rector\Class_\LoadValidatorMetadataToAttributeRector::class,
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class => [
            __DIR__.'/app/bundles/PageBundle/Form/Type/PreferenceCenterListType.php',
        ],

        // test fixtures
        __DIR__.'/plugins/*/node_modules/*',
        __DIR__.'/app/tests/CoreBundle/Tests/Unit/Helper/resource/',

        UnserializeToSerializerDecodeRector::class => [
            // tests
            __DIR__.'/app/tests/UserBundle/Tests/Entity/UserTest.php',
            // reproduces what DBAL's own ArrayType did before DBAL 4 removed it: the objects
            // have to be unserialized so that convertToPHPValue can drop the ones that would
            // carry null bytes. Serializer::decode() refuses the whole value instead, which
            // loses array fields that legitimately hold one.
            __DIR__.'/app/bundles/CoreBundle/Doctrine/Type/ArrayType.php',
        ],

        // streamed response above
        Rector\CodeQuality\Rector\ClassMethod\ExplicitReturnNullRector::class => [
            __DIR__.'/app/bundles/ReportBundle/Model/ReportModel.php',
        ],

        Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector::class => [
            __DIR__.'/app/bundles/PageBundle/Controller/AjaxController.php',
            __DIR__.'/app/bundles/EmailBundle/Controller/AjaxController.php',
            // protected methods are mocked in tests
            __DIR__.'/app/bundles/PageBundle/Model/TrackableModel.php',
            __DIR__.'/plugins/MauticCrmBundle/Integration/SalesforceIntegration.php',
        ],

        // modified with reflection
        Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Helper/MailHelper.php',
        ],
    ])
    ->reportUnusedSkips();
