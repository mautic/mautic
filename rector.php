<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Class_\ReturnTypeFromStrictTernaryRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\NumericReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictParamRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ]);

    $rectorConfig->skip([
        '*/Test/*',
        '*/Tests/*',

        ReturnTypeFromReturnDirectArrayRector::class => [
            // require bit test update
            __DIR__.'/app/bundles/LeadBundle/Model/LeadModel.php',
            // array vs doctrine collection
            __DIR__.'/app/bundles/CoreBundle/Entity/TranslationEntityTrait.php',
        ],

        // Avoiding breaking BC breaks with forced return types in public methods
        ReturnTypeFromReturnNewRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Integration/ObjectChangeGenerator.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/SyncProcess/Direction/Internal/ObjectChangeGenerator.php',
        ],

        // lets handle later, once we have more type declaratoins
        RecastingRemovalRector::class,

        RemoveUnusedPrivatePropertyRector::class => [
            // entities
            __DIR__.'/app/bundles/UserBundle/Entity',
            // typo fallback
            __DIR__.'/app/bundles/LeadBundle/Entity/LeadField.php',
        ],

        RemoveUnusedVariableAssignRector::class => [
            // unset variable to clear garbage collector
            __DIR__.'/app/bundles/LeadBundle/Model/ImportModel.php',
        ],

        TypedPropertyFromStrictConstructorRector::class => [
            // entities magic
            __DIR__.'/app/bundles/LeadBundle/Entity',

            // fixed in rector dev-main
            __DIR__.'/app/bundles/CoreBundle/DependencyInjection/Builder/BundleMetadata.php',
        ],

        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/app/bundles/CacheBundle/EventListener/CacheClearSubscriber.php',
            __DIR__.'/app/bundles/ReportBundle/Event/ReportBuilderEvent.php',
            // false positive
            __DIR__.'/app/bundles/CoreBundle/DependencyInjection/Builder/BundleMetadata.php',
        ],

        Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector::class => [
            '*/Entity/*',
        ],

        // handle later with full PHP 8.0 upgrade
        Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector::class,

        // handle later, case by case as lot of chnaged code
        Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__.'/app/bundles/PointBundle/Controller/TriggerController.php',
            __DIR__.'/app/bundles/LeadBundle/Controller/ImportController.php',
            __DIR__.'/app/bundles/FormBundle/Controller/FormController.php',
            // watch out on this one - the variables are set magically via $$name
            // @see app/bundles/FormBundle/Form/Type/FieldType.php:99
            __DIR__.'/app/bundles/FormBundle/Form/Type/FieldType.php',
        ],
    ]);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        SetList::DEAD_CODE,
        SetList::PHP_80,
        // SetList::TYPE_DECLARATION,
    ]);

    // Define what single rules will be applied
    $rectorConfig->rules([
        Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector::class,

        Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector::class,
        NumericReturnTypeFromStrictScalarReturnsRector::class,
        ReturnTypeFromReturnNewRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictNewArrayRector::class,
        ReturnTypeFromStrictParamRector::class,
        ReturnTypeFromStrictTernaryRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictSetUpRector::class,
        RemoveUnusedVariableAssignRector::class,
        RemoveUselessVarTagRector::class,
        SimplifyUselessVariableRector::class,
        ReturnTypeFromStrictConstantReturnRector::class,
        ReturnTypeFromReturnDirectArrayRector::class,
    ]);
};
