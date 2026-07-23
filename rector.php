<?php

declare(strict_types=1);

use Mautic\CoreBundle\Entity\CommonRepository;
use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ResponseReturnTypeControllerActionRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StringReturnTypeFromStrictStringReturnsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
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
        MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration::class,
        Mautic\PluginBundle\Integration\AbstractIntegration::class,
    ])
    ->withRules([
        Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AssertClassToThisAssertRector::class,
        Rector\TypeDeclarationDocblocks\Rector\Property\MergePhpstanDocTagIntoNativeRector::class,

        Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector::class,
        Rector\TypeDeclarationDocblocks\Rector\ClassMethod\NarrowArrayCollectionUnionReturnDocblockRector::class,
        TypedPropertyFromAssignsRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        SimplifyUselessVariableRector::class,
        UnserializeToSerializerDecodeRector::class,
        Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class,
        Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class,

        // symfony
        Rector\Symfony\Symfony73\Rector\Class_\CommandDefaultNameAndDescriptionToAsCommandAttributeRector::class,
        Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector::class,
        Rector\Symfony\Symfony73\Rector\Class_\CommandHelpToAttributeRector::class,
    ])
    ->reportUnusedSkips()
    ->withCodingStyleLevel(3)
    ->withSkip([
        __DIR__.'/plugins/*/node_modules/*',

        Rector\TypeDeclaration\Rector\ClassMethod\ArrayParamTypeByMethodCallTypeRector::class => [
            __DIR__.'/app/bundles/LeadBundle/Entity/CustomFieldEntityTrait.php',
        ],

        UnserializeToSerializerDecodeRector::class => [
            // tests
            __DIR__.'/app/bundles/UserBundle/Tests/Entity/UserTest.php',
        ],

        // to be fixed in dev-main
        Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector::class => [
            '*Command.php',
        ],

        // skip as might be overriden by 3rd party controllers
        ResponseReturnTypeControllerActionRector::class => [
            __DIR__.'/app/bundles/ApiBundle/Controller/CommonApiController.php',
            __DIR__.'/app/bundles/ApiBundle/Controller/FetchCommonApiController.php',
            __DIR__.'/app/bundles/CoreBundle/Controller/AbstractFormController.php',
            __DIR__.'/app/bundles/CoreBundle/Controller/CommonController.php',
        ],
        Rector\TypeDeclaration\Rector\ClassMethod\ScalarParamTypeByMethodCallTypeRector::class => [
            __DIR__.'/app/bundles/PageBundle/Model/TrackableModel.php',
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

        Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector::class,

        // will be fixed
        Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector::class,

        Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector::class => [
            __DIR__.'/app/bundles/EmailBundle/Tests/Model/EmailModelTest.php',
        ],

        // handle later with full PHP 8.0 upgrade
        OptionalParametersAfterRequiredRector::class,
    ]);
