<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Symfony\Symfony42\Rector\MethodCall\ContainerGetToConstructorInjectionRector;

return static function (Rector\Config\RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ]);

    $rectorConfig->skip([
        '*/Test/*',
        '*/Tests/*',
        '*.html.php',
        ContainerGetToConstructorInjectionRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Factory/MauticFactory.php', // Requires quite a refactoring.
        ],
    ]);

    $rectorConfig->parallel();

    foreach (['dev', 'test', 'prod'] as $environment) {
        $environmentCap = ucfirst($environment);
        $xmlPath        = __DIR__."/var/cache/{$environment}/appAppKernel{$environmentCap}DebugContainer.xml";
        if (file_exists($xmlPath)) {
            $rectorConfig->symfonyContainerXml($xmlPath);
            break;
        }
    }

    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->cacheDirectory(__DIR__.'/var/cache/rector');

    // Define what rule sets will be applied
    $rectorConfig->sets([
        // helps with rebase of PRs for Symfony 3 and 4, @see https://github.com/mautic/mautic/pull/12676#issuecomment-1695531274
        // remove when not needed to keep memory usage lower
        \Rector\Symfony\Set\SymfonyLevelSetList::UP_TO_SYMFONY_54,

        \Rector\Doctrine\Set\DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_CODE_QUALITY,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_COMMON_20,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_211,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_30,
        // \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_40, this rule should run after the upgrade to doctrine 4.0
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_213,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_214,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_29,
        // \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_REPOSITORY_AS_SERVICE, will break code in Mautic, needs to be fixed first
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_25,

        // @todo implement the whole set. Start rule by rule below.
        // \Rector\Set\ValueObject\SetList::DEAD_CODE
    ]);

    // Define what single rules will be applied
    $rectorConfig->rules([
        \Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector::class,
        \Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector::class,
        \Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector::class,
        \Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class,
        \Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class,
        \Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector::class,
        \Rector\DeadCode\Rector\For_\RemoveDeadContinueRector::class,
        \Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector::class,
        \Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class,
        ContainerGetToConstructorInjectionRector::class,
    ]);
};
