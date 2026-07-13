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
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
        phpunitCodeQuality: true,
    )
    ->withPhpSets()
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
        Mautic\PluginBundle\Integration\AbstractIntegration::class,
        MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::class,
    ])
    ->withRules([
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,

        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        TypedPropertyFromAssignsRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        SimplifyUselessVariableRector::class,
        UnserializeToSerializerDecodeRector::class,
        Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class,
        Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class,
    ])
    ->reportUnusedSkips()
    ->withCodingStyleLevel(3)
    ->withSkip([
        __DIR__.'/plugins/*/node_modules/*',

        Rector\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeRector::class => [
            __DIR__.'/app/bundles/LeadBundle/Entity/CustomFieldEntityTrait.php',
        ],

<<<<<<< HEAD
        Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Tests/Unit/Twig/Helper/FormatterHelperTest.php',
        ],

=======
        // waits for descission
        Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector::class,

<<<<<<< HEAD
<<<<<<< HEAD
>>>>>>> 23e1b63f55 (remove fixed rector rules)
        // fix in rector-dev
        Rector\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/CommonRepository.php',
        ],

        Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Model/VariantModelTrait.php',
        ],

        // offer next
        Rector\CodeQuality\Rector\If_\ArrayExplicitBoolCompareRector::class,

=======
>>>>>>> e95ccb7c08 (remove fixed rector rules)
=======
>>>>>>> d3a3fb6fc2 (use direct variables over compact to enable static analysis + fill expects to returm map in tests)
        UnserializeToSerializerDecodeRector::class => [
            // tests
            __DIR__.'/app/bundles/UserBundle/Tests/Entity/UserTest.php',
        ],

        Rector\CodeQuality\Rector\If_\CombineIfRector::class,
        Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,

        Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromGetRepositoryDocblockRector::class => [
            // a getRepository() override
            __DIR__.'/app/bundles/LeadBundle/Model/TagModel.php',
            // list lead vs lead list diff
            __DIR__.'/app/bundles/LeadBundle/Model/ListModel.php',
        ],

        Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector::class,

        // modified with reflection
        Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Entity/EmailDraft.php',
            __DIR__.'/app/bundles/EmailBundle/Helper/MailHelper.php',
            __DIR__.'/app/bundles/CoreBundle/Twig/Helper/DateHelper.php',
        ],

        // too many changes
        Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector::class,
        Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class,

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

        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,

        StringReturnTypeFromStrictStringReturnsRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Entity/FormEntity.php',
        ],

<<<<<<< HEAD
        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,

=======
        // phpunit
        Rector\PHPUnit\CodeQuality\Rector\Class_\AssertClassToThisAssertRector::class,
        Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector::class,
<<<<<<< HEAD
>>>>>>> fab4e2d24f (cleanup skipped never used rules)
        // will be fixed
        Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector::class,
=======

        Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Tests/Model/EmailModelTest.php',
        ],
>>>>>>> d3a3fb6fc2 (use direct variables over compact to enable static analysis + fill expects to returm map in tests)

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,
    ]);
