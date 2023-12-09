<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Symfony\Symfony42\Rector\MethodCall\ContainerGetToConstructorInjectionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\BoolReturnTypeFromStrictScalarReturnsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictSetUpRector;

return static function (Rector\Config\RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ]);

    $rectorConfig->skip([
        \Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector::class,

        '*/Test/*',
        '*/Tests/*',
        '*.html.php',
        ContainerGetToConstructorInjectionRector::class => [
            // Requires quite a refactoring
            __DIR__.'/app/bundles/CoreBundle/Factory/MauticFactory.php',
        ],

        ReturnTypeFromReturnDirectArrayRector::class => [
            // require bit test update
            __DIR__.'/app/bundles/LeadBundle/Model/LeadModel.php',
            // array vs doctrine collection
            __DIR__.'/app/bundles/CoreBundle/Entity/TranslationEntityTrait.php',
        ],

        // lets handle later, once we have more type declaratoins
        \Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class,

        \Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class => [
            // entities
            __DIR__.'/app/bundles/UserBundle/Entity',
            // typo fallback
            __DIR__.'/app/bundles/LeadBundle/Entity/LeadField.php',
        ],

        ReturnTypeFromStrictBoolReturnExprRector::class => [
            __DIR__.'/app/bundles/LeadBundle/Segment/Decorator/BaseDecorator.php',
            // requires quite a refactoring
            __DIR__.'/app/bundles/CoreBundle/Factory/MauticFactory.php',
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

        \Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/app/bundles/CacheBundle/EventListener/CacheClearSubscriber.php',
            __DIR__.'/app/bundles/ReportBundle/Event/ReportBuilderEvent.php',
            // false positive
            __DIR__.'/app/bundles/CoreBundle/DependencyInjection/Builder/BundleMetadata.php',
        ],

        // handle later with full PHP 8.0 upgrade
        \Rector\Php80\Rector\FunctionLike\MixedTypeRector::class,
        \Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector::class,
        \Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector::class,
        \Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector::class,

        // handle later, case by case as lot of chnaged code
        \Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class => [
            __DIR__.'/app/bundles/PointBundle/Controller/TriggerController.php',
            __DIR__.'/app/bundles/LeadBundle/Controller/ImportController.php',
            __DIR__.'/app/bundles/FormBundle/Controller/FormController.php',
            // watch out on this one - the variables are set magically via $$name
            // @see app/bundles/FormBundle/Form/Type/FieldType.php:99
            __DIR__.'/app/bundles/FormBundle/Form/Type/FieldType.php',
        ],
    ]);

//    foreach (['dev', 'test', 'prod'] as $environment) {
//        $environmentCap = ucfirst($environment);
//        $xmlPath        = __DIR__."/var/cache/{$environment}/appAppKernel{$environmentCap}DebugContainer.xml";
//        if (file_exists($xmlPath)) {
//            $rectorConfig->symfonyContainerXml($xmlPath);
//            break;
//        }
//    }
//
//    $rectorConfig->cacheClass(FileCacheStorage::class);
//    $rectorConfig->cacheDirectory(__DIR__.'/var/cache/rector');

    $rectorConfig->sets([
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        \Rector\Symfony\Set\SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->phpVersion(\Rector\Core\ValueObject\PhpVersion::PHP_80);
};
