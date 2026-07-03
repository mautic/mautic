<?php

declare(strict_types=1);

use Mautic\CoreBundle\Entity\CommonRepository;
use MauticRector\UnserializeToSerializerDecodeRector;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withPreparedSets(deadCode: true)
    ->withPhpSets(php80: true)
    ->withCache(__DIR__.'/var/cache/rector')
    ->withTypeGuardedClasses([
        Mautic\CoreBundle\Controller\AbstractStandardFormController::class,
        Mautic\CoreBundle\Controller\CommonController::class,
        Mautic\CoreBundle\Controller\AbstractFormController::class,
        CommonRepository::class,
        Mautic\ApiBundle\Controller\FetchCommonApiController::class,
    ])
    ->withRules([
        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\ChangeMockObjectReturnUnionToIntersectionRector::class,

        TypedPropertyFromAssignsRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        SimplifyUselessVariableRector::class,
        UnserializeToSerializerDecodeRector::class,
    ])
    ->reportUnusedSkips()
    ->withTypeCoverageLevel(50)
    ->withCodingStyleLevel(3)
    ->withCodeQualityLevel(27)
    ->withSkip([
        UnserializeToSerializerDecodeRector::class => [
            // tests
            __DIR__.'/app/bundles/UserBundle/Tests/Entity/UserTest.php',
        ],

        Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayMapRector::class => [
            __DIR__.'/app/bundles/SmsBundle/Controller/AjaxController.php',
        ],

        Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,
        // modified with reflection
        Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Entity/EmailDraft.php',
            __DIR__.'/app/bundles/EmailBundle/Helper/MailHelper.php',
        ],

        // too many changes
        Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,

        Rector\Renaming\Rector\FuncCall\RenameFunctionRector::class,

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

        StringReturnTypeFromStrictStringReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/FormEntity.php',
        ],
        Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector::class => [
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
        ],

        Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector::class => [
            // date times
            __DIR__.'/app/bundles/CampaignBundle/Executioner/EventExecutioner.php',
            __DIR__.'/app/bundles/IntegrationsBundle/Sync/DAO/DateRange.php',
        ],

        TypedPropertyFromAssignsRector::class => [
            '*/Entity/*',
        ],

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,
    ]);
