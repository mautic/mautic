<?php

declare(strict_types=1);

use MauticRector\UnserializeToSerializerDecodeRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

$extendableControllers = [
    __DIR__.'/app/bundles/CoreBundle/Controller/AbstractStandardFormController.php',
    __DIR__.'/app/bundles/CoreBundle/Controller/CommonController.php',
    __DIR__.'/app/bundles/CoreBundle/Controller/FormController.php',
];

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withPreparedSets(deadCode: true)
    ->withPhpSets(php80: true)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withRules([
        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        AddParamTypeFromPropertyTypeRector::class,
        ClosureReturnTypeRector::class,

        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromStrictParamRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        SimplifyUselessVariableRector::class,
        UnserializeToSerializerDecodeRector::class,
    ])
    ->reportUnusedSkips()
    ->withTypeCoverageLevel(36)
    ->withCodingStyleLevel(3)
    ->withCodeQualityLevel(27)
    ->withSkip([
        Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
        // modified with reflection
        Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Entity/EmailDraft.php',
            __DIR__.'/app/bundles/EmailBundle/Helper/MailHelper.php',
        ],

        // too many changes
        Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,
        // soon to be deprecated
        Rector\CodeQuality\Rector\Concat\JoinStringConcatRector::class,

        Rector\Renaming\Rector\FuncCall\RenameFunctionRector::class,
        '*/Test/*',
        '*/Tests/*',

        // Avoiding breaking BC breaks with forced return types in public methods
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Integration/ObjectChangeGenerator.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Internal/ObjectChangeGenerator.php',
        ],

        // lets handle later, once we have more type declaratoins
        RecastingRemovalRector::class,

        Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class => [
            // test fixture
            __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Doctrine/ArrayTypeTest.php',
        ],

        // designed to be overriden by 3rd party, adding return type will break BC
        Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector::class => [
            ...$extendableControllers,
        ],
        ReturnTypeFromStrictTypedCallRector::class => [
            ...$extendableControllers,
        ],
        StringReturnTypeFromStrictStringReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/FormEntity.php',
        ],
        ReturnTypeFromStrictTypedPropertyRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Controller/FormController.php',
            // handle mocks later
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
            __DIR__.'/app/bundles/CampaignBundle/Executioner/EventExecutioner.php',
        ],
        Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
            // can be overriden, BC
            ...$extendableControllers,
        ],

        TypedPropertyFromAssignsRector::class => [
            '*/Entity/*',
        ],

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,
    ]);
