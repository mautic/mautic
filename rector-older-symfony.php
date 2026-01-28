<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
    ])
    ->withCache(__DIR__.'/var/cache/rector-older-symfony')
    ->withSymfonyContainerXml(__DIR__.'/var/cache/dev/appAppKernelDevDebugContainer.xml')
    ->withSets([
        // helps with rebase of PRs for Symfony 3 and 4, @see https://github.com/mautic/mautic/pull/12676#issuecomment-1695531274
        // remove when not needed to keep memory usage lower
        SymfonySetList::SYMFONY_70,

        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_COMMON_20,
        DoctrineSetList::DOCTRINE_DBAL_211,
        DoctrineSetList::DOCTRINE_DBAL_30,
        DoctrineSetList::DOCTRINE_ORM_213,
        DoctrineSetList::DOCTRINE_ORM_214,
        DoctrineSetList::DOCTRINE_ORM_29,
        DoctrineSetList::DOCTRINE_ORM_25,
    ]);
