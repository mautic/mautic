<?php

declare(strict_types=1);

use MauticRector\AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\ParentTestClassConstructorRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\PHPUnit\PHPUnit80\Rector\MethodCall\SpecificAssertContainsRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\Symfony43\Rector\MethodCall\WebTestCaseAssertIsSuccessfulRector;
use Rector\Symfony\Symfony43\Rector\MethodCall\WebTestCaseAssertResponseCodeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app/bundles/*/Test',
        __DIR__.'/app/bundles/*/Tests',
        __DIR__.'/plugins/*/Test',
        __DIR__.'/plugins/*/Tests',
    ])
    ->withCache(__DIR__.'/var/cache/rector-tests')
    ->withSets([
        PHPUnitSetList::PHPUNIT_60,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
    ])
    ->withRules([
        SpecificAssertContainsRector::class,
        AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector::class,
        RemoveUnusedVariableAssignRector::class,
        WebTestCaseAssertResponseCodeRector::class,
        WebTestCaseAssertIsSuccessfulRector::class,
    ])
    ->withSkip([
        AddDoesNotPerformAssertionToNonAssertingTestRector::class, // Adds annotation where it does not belong to.
        ParentTestClassConstructorRector::class, // Adds unnecessary constructors to test classes without custom logic.
        WebTestCaseAssertResponseCodeRector::class => [
            __DIR__.'/app/bundles/FormBundle/Tests/Controller/SubmissionFunctionalTest.php',
            __DIR__.'/app/bundles/MarketplaceBundle/Tests/Functional/Controller/AjaxControllerTest.php',
        ],
        WebTestCaseAssertIsSuccessfulRector::class => [
            __DIR__.'/app/bundles/CoreBundle/Tests/Functional/SamlTest.php',
            __DIR__.'/app/bundles/MarketplaceBundle/Tests/Functional/Controller/AjaxControllerTest.php',
        ],
    ]);
