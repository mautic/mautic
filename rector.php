<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // here we can define, what sets of rules will be applied
    $parameters = $containerConfigurator->parameters();

    // Define what rule sets will be applied
    // $containerConfigurator->import(SetList::DEAD_CODE);

    $parameters->set(
        Option::PATHS,
        [
            __DIR__.'/app/bundles',
            __DIR__.'/plugins',
        ]
    );

    $parameters->set(
        Option::SKIP,
        [
            __DIR__.'/*/test/*',
            __DIR__.'/*/tests/*',
            __DIR__.'/*/Test/*',
            __DIR__.'/*/Tests/*',
            __DIR__.'/*/InstallFixtures/*',
            __DIR__.'/*/Fixtures/*',
            __DIR__.'/*.html.php',
            __DIR__.'/*.less.php',
            __DIR__.'/*.inc.php',
            __DIR__.'/*.js.php',
            __DIR__.'/app/bundles/LeadBundle/Entity/LeadField.php',
            __DIR__.'/app/bundles/WebhookBundle/Entity/Webhook.php',
            __DIR__.'/app/bundles/UserBundle/Entity/UserToken.php',
            __DIR__.'/app/bundles/EmailBundle/Entity/EmailReplyRepository.php',
        ]
    );

    $services = $containerConfigurator->services();
    $services->set(RemoveAndTrueRector::class);
    // $services->set(RemoveAlwaysTrueIfConditionRector::class); // 41 files would have changed (we should have a separate PR to enable this)
};
