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
        codeQuality: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        symfonyCodeQuality: true,
        phpunitCodeQuality: true,
        phpunitMockToStub: true,
        phpunitNarrowAsserts: true,
    )
    ->withPhpSets(php84: true)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withRules([
        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AssertClassToThisAssertRector::class,
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,
        // custom rules
        UnserializeToSerializerDecodeRector::class,
        Utils\Rector\AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector::class,
        Utils\Rector\ModelGetRepositoryToRepositoryServiceRector::class,
    ])
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        // handle later
        Rector\PHPUnit\PHPUnit120\Rector\Class_\AllowMockObjectsForDataProviderRector::class,

        // called globally
        Rector\TypeDeclarationDocblocks\Rector\Class_\ClassMethodArrayDocblockParamFromLocalCallsRector::class => [
            __DIR__.'/plugins/MauticCrmBundle/Integration/SalesforceIntegration.php',
            __DIR__.'/app/bundles/CoreBundle/Controller/AjaxController.php',
        ],
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\AddParamArrayDocblockFromDimFetchAccessRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Controller/AjaxController.php',
        ],

        // prefer implicit compare on object|null
        Rector\TypeDeclaration\Rector\BooleanAnd\BinaryOpNullableToInstanceofRector::class,
        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,
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
        ],

        // test fixture
        Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Doctrine/ArrayTypeTest.php',
        ],
    ])
    ->reportUnusedSkips();
