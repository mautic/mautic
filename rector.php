<?php

declare(strict_types=1);

use MauticRector\UnserializeToSerializerDecodeRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
// use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withPreparedSets(deadCode: true)
    ->withPhpSets(php80: true)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withRules([
        ReturnTypeFromStrictTypedCallRector::class,
        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromReturnNewRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictParamRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        SimplifyUselessVariableRector::class,
        ReturnTypeFromReturnDirectArrayRector::class,
        UnserializeToSerializerDecodeRector::class,
    ])
    ->reportUnusedSkips()
    ->withTypeCoverageLevel(15)
    ->withCodeQualityLevel(2)
    ->withSkip([
        Rector\Renaming\Rector\FuncCall\RenameFunctionRector::class,
        '*/Test/*',
        '*/Tests/*',

        ReturnTypeFromReturnDirectArrayRector::class => [
            // require bit test update
            __DIR__.'/app/bundles/LeadBundle/Model/LeadModel.php',
        ],

        // Avoiding breaking BC breaks with forced return types in public methods
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Integration/ObjectChangeGenerator.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Internal/ObjectChangeGenerator.php',
        ],

        // lets handle later, once we have more type declaratoins
        RecastingRemovalRector::class,

        // designed to be overriden by 3rd party, adding return type will break BC
        Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictScalarReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Controller/CommonController.php',
            __DIR__.'/app/bundles/CoreBundle/Controller/AbstractStandardFormController.php',
        ],
        StringReturnTypeFromStrictStringReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/FormEntity.php',
        ],

        TypedPropertyFromAssignsRector::class => [
            '*/Entity/*',
        ],

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,

        // handle later, case by case as lot of chnaged code
        RemoveAlwaysTrueIfConditionRector::class => [
            // watch out on this one - the variables are set magically via $$name
            // @see app/bundles/FormBundle/Form/Type/FieldType.php:99
            __DIR__.'/app/bundles/FormBundle/Form/Type/FieldType.php',
        ],
    ]);
