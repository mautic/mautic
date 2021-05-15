<?php

use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\Class_\RemoveUnusedDoctrineEntityMethodAndPropertyRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDeadRecursiveClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedParameterRector;
use Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Rector\DeadCode\Rector\MethodCall\RemoveDefaultArgumentValueRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // here we can define, what sets of rules will be applied
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SETS, [SetList::DEAD_CODE]);

    $parameters->set(
        Option::PATHS,
        [
            __DIR__ . '/app/bundles',
            __DIR__ . '/plugins'
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
            __DIR__ . '/*/tests/*',
            __DIR__ . '/*/tests/*',
            __DIR__ . '/*/Tests/*',
            __DIR__ . '/*/Test/*',
            __DIR__ . '/*/test/*',
            __DIR__ . '/*/InstallFixtures/*',
            __DIR__ . '/*/Fixtures/*',
            __DIR__ . '/*.html.php',
            __DIR__ . '/*.less.php',
            __DIR__ . '/*.inc.php',
            __DIR__ . '/*.js.php',
            __DIR__ . '/app/bundles/LeadBundle/Entity/LeadField.php',
            __DIR__ . '/app/bundles/WebhookBundle/Entity/Webhook.php',
            __DIR__ . '/app/bundles/UserBundle/Entity/UserToken.php',
            __DIR__ . '/app/bundles/EmailBundle/Entity/EmailReplyRepository.php',
            RemoveUnusedParameterRector::class, # Causes BC breaks and broken services. Use only manually with caution.
            RemoveDefaultArgumentValueRector::class, # Doesn't play nicely every time.
            SimplifyIfElseWithSameContentRector::class, # Removes code that is not the same.
            RemoveDeadIfForeachForRector::class, # Problematic with some copy-pasted code.
            TernaryToBooleanOrFalseToBooleanAndRector::class, # see https://github.com/rectorphp/rector/issues/2765
            RemoveDuplicatedCaseInSwitchRector::class, # see https://github.com/rectorphp/rector/issues/2730
            RecastingRemovalRector::class, # Does some dangerous changes in e.g. queries
            RemoveUnusedDoctrineEntityMethodAndPropertyRector::class, # Incorrectly removes loadMetadata() methods
            RemoveUnusedPrivatePropertyRector::class, # Incorrectly removes some private properties
            RemoveUnusedClassConstantRector::class, # Incorrectly removes some class constants that we use
            RemoveDeadRecursiveClassMethodRector::class # Changed quite some code, didn't have time to check if it's correct
        ]
    );

    // register single rule
    //$services = $containerConfigurator->services();
    //$services->set(TypedPropertyRector::class);
};
