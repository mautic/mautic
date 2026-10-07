<?php

declare(strict_types=1);

/**
 * Move bundle tests out of production source into a dev-only tests root.
 *
 * - app/bundles/*Bundle/Tests -> app/tests/*Bundle/Tests (namespaces unchanged)
 * - registers app/tests under root composer.json autoload-dev
 * - repoints phpunit, phpstan and rector paths
 * - fixes the few tests that resolve paths relative to their old location
 *
 * Run from repo root: php script/migrate-test.php
 */
$root = dirname(__DIR__);

function loadFile(string $file): string
{
    $content = file_get_contents($file);
    if ($content === false) {
        fail("cannot read {$file}");
    }

    return $content;
}

function saveFile(string $file, string $content): void
{
    if (file_put_contents($file, $content) === false) {
        fail("cannot write {$file}");
    }
}

function replaceOnce(string $content, string $search, string $replace, string $context): string
{
    if (!str_contains($content, $search)) {
        fail("anchor not found in {$context}: {$search}");
    }

    return str_replace($search, $replace, $content);
}

function fail(string $message): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

// 1. move app/bundles/*Bundle/Tests -> app/tests/*Bundle/Tests
$bundlesDir = $root.'/app/bundles';
$testsRoot  = $root.'/app/tests';

$moved = 0;
foreach (glob($bundlesDir.'/*Bundle/Tests', GLOB_ONLYDIR) as $sourceTestsDir) {
    $bundleName = basename(dirname($sourceTestsDir));
    $targetDir  = $testsRoot.'/'.$bundleName.'/Tests';

    if (is_dir($targetDir)) {
        echo "skip (exists): app/tests/{$bundleName}/Tests\n";
        continue;
    }

    $parent = dirname($targetDir);
    if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) {
        fail("cannot create {$parent}");
    }

    if (!rename($sourceTestsDir, $targetDir)) {
        fail("cannot move {$sourceTestsDir} -> {$targetDir}");
    }

    // keep DataFixtures in production source; they are registered as services
    $movedFixtures = $targetDir.'/DataFixtures';
    if (is_dir($movedFixtures)) {
        $fixturesTarget = $sourceTestsDir.'/DataFixtures';
        if (!mkdir($sourceTestsDir, 0777, true) && !is_dir($sourceTestsDir)) {
            fail("cannot create {$sourceTestsDir}");
        }
        if (!rename($movedFixtures, $fixturesTarget)) {
            fail("cannot move {$movedFixtures} -> {$fixturesTarget}");
        }
        echo "kept fixtures: app/bundles/{$bundleName}/Tests/DataFixtures\n";
    }

    echo "moved: app/bundles/{$bundleName}/Tests -> app/tests/{$bundleName}/Tests\n";
    ++$moved;
}
echo "moved {$moved} bundle test directories\n\n";

// 2. register the tests root under composer.json autoload-dev
$composerFile = $root.'/composer.json';
$composer     = loadFile($composerFile);

$composerAnchor = <<<'TXT'
  "autoload-dev": {
    "psr-4": {
      "Utils\\Rector\\": "utils/rector/src/",
TXT;

$composerReplacement = <<<'TXT'
  "autoload-dev": {
    "psr-4": {
      "Mautic\\": "app/tests/",
      "Utils\\Rector\\": "utils/rector/src/",
TXT;

if (str_contains($composer, $composerReplacement)) {
    echo "composer.json: Mautic autoload-dev already present\n";
} else {
    saveFile($composerFile, replaceOnce($composer, $composerAnchor, $composerReplacement, 'composer.json'));
    echo "composer.json: registered Mautic -> app/tests/\n";
}

// 3. repoint phpunit paths
$phpunitFile = $root.'/app/phpunit.xml.dist';
$phpunit     = loadFile($phpunitFile);

// drop the Tests coverage exclude; tests no longer live under the included source
$phpunit = replaceOnce(
    $phpunit,
    <<<'TXT'
      <directory>bundles/*Bundle/DataFixtures</directory>
      <directory>bundles/*Bundle/Tests</directory>
      <directory>bundles/*Bundle/Translations</directory>
TXT,
    <<<'TXT'
      <directory>bundles/*Bundle/DataFixtures</directory>
      <directory>bundles/*Bundle/Translations</directory>
TXT,
    'phpunit.xml.dist (source exclude)'
);

// repoint the project + smoke test suites to the new tests root
$phpunit = replaceOnce(
    $phpunit,
    <<<'TXT'
      <directory>bundles/*Bundle/Tests</directory>
      <exclude>bundles/CoreBundle/Tests/Functional/DependencyInjection/ControllerSmokeTest.php</exclude>
      <exclude>bundles/CoreBundle/Tests/Functional/DependencyInjection/CommandSmokeTest.php</exclude>
      <exclude>bundles/CoreBundle/Tests/Functional/DependencyInjection/EventSubscriberSmokeTest.php</exclude>
    </testsuite>
    <testsuite name="Container smoke tests">
      <file>bundles/CoreBundle/Tests/Functional/DependencyInjection/ControllerSmokeTest.php</file>
      <file>bundles/CoreBundle/Tests/Functional/DependencyInjection/CommandSmokeTest.php</file>
      <file>bundles/CoreBundle/Tests/Functional/DependencyInjection/EventSubscriberSmokeTest.php</file>
TXT,
    <<<'TXT'
      <directory>tests/*Bundle/Tests</directory>
      <exclude>tests/CoreBundle/Tests/Functional/DependencyInjection/ControllerSmokeTest.php</exclude>
      <exclude>tests/CoreBundle/Tests/Functional/DependencyInjection/CommandSmokeTest.php</exclude>
      <exclude>tests/CoreBundle/Tests/Functional/DependencyInjection/EventSubscriberSmokeTest.php</exclude>
    </testsuite>
    <testsuite name="Container smoke tests">
      <file>tests/CoreBundle/Tests/Functional/DependencyInjection/ControllerSmokeTest.php</file>
      <file>tests/CoreBundle/Tests/Functional/DependencyInjection/CommandSmokeTest.php</file>
      <file>tests/CoreBundle/Tests/Functional/DependencyInjection/EventSubscriberSmokeTest.php</file>
TXT,
    'phpunit.xml.dist (test suites)'
);

saveFile($phpunitFile, $phpunit);
echo "phpunit.xml.dist: repointed test suites to tests/*Bundle/Tests\n";

// 4. repoint phpstan paths
$phpstanFile = $root.'/phpstan.neon';
$phpstan     = loadFile($phpstanFile);

$phpstan = replaceOnce(
    $phpstan,
    <<<'TXT'
        - app/bundles
        - app/config
TXT,
    <<<'TXT'
        - app/bundles
        - app/tests
        - app/config
TXT,
    'phpstan.neon (paths)'
);

$phpstan = rewriteTestPaths($phpstan);
saveFile($phpstanFile, $phpstan);
echo "phpstan.neon: added app/tests and repointed ignored test paths\n";

// 4b. repoint the phpstan baseline so its ignored test errors keep matching
$baselineFile = $root.'/phpstan-baseline.neon';
$baseline     = loadFile($baselineFile);
saveFile($baselineFile, rewriteTestPaths($baseline));
echo "phpstan-baseline.neon: repointed baselined test paths\n";

// 5. repoint rector paths
$rectorFile = $root.'/rector.php';
$rector     = loadFile($rectorFile);

$rector = replaceOnce(
    $rector,
    <<<'TXT'
        __DIR__.'/app/bundles',
        __DIR__.'/plugins',
TXT,
    <<<'TXT'
        __DIR__.'/app/bundles',
        __DIR__.'/app/tests',
        __DIR__.'/plugins',
TXT,
    'rector.php (paths)'
);

$rector = rewriteTestPaths($rector);
saveFile($rectorFile, $rector);
echo "rector.php: added app/tests and repointed skipped test paths\n";

// 6. fix tests that resolved paths relative to their old location
$testFixes = [
    'app/tests/CoreBundle/Tests/Command/PushTransifexCommandFunctionalTest.php' => [
        ["realpath(__DIR__.'/../../..')", "realpath(__DIR__.'/../../../../bundles')"],
    ],
    'app/tests/CoreBundle/Tests/Unit/DependencyInjection/Builder/Metadata/EntityMetadataTest.php' => [
        ["__DIR__.'/../../../../../',", "__DIR__.'/../../../../../../../bundles/CoreBundle/',"],
    ],
    'app/tests/CoreBundle/Tests/Unit/DependencyInjection/Builder/Metadata/PermissionClassMetadataTest.php' => [
        ["__DIR__.'/../../../../../../AssetBundle',", "__DIR__.'/../../../../../../../bundles/AssetBundle',"],
        ["__DIR__.'/../../../../../',", "__DIR__.'/../../../../../../../bundles/CoreBundle/',"],
    ],
    'app/tests/CoreBundle/Tests/Unit/Twig/Helper/TableHeaderTest.php' => [
        ["__DIR__.'/../../../../Resources/views/Helper'", "__DIR__.'/../../../../../../bundles/CoreBundle/Resources/views/Helper'"],
    ],
    'app/tests/WebhookBundle/Tests/Unit/Controller/WebhookControllerTest.php' => [
        ["realpath(__DIR__.'/../../../../')", "realpath(__DIR__.'/../../../../../bundles')"],
        ['dirname(__DIR__, 4)', "dirname(__DIR__, 5).'/bundles'"],
    ],
    // S/MIME tests hardcode the moved cert dir as a %kernel.project_dir% string
    'app/tests/EmailBundle/Tests/Controller/Api/SMimeFunctionalTest.php' => [
        ['app/bundles/EmailBundle/Tests/Mocks/', 'app/tests/EmailBundle/Tests/Mocks/'],
    ],
    'app/tests/LeadBundle/Tests/Functional/Controller/SendEmailToContactTest.php' => [
        ['app/bundles/EmailBundle/Tests/Mocks/', 'app/tests/EmailBundle/Tests/Mocks/'],
    ],
    'app/tests/CoreBundle/Tests/Functional/RouteMapTest.php' => [
        ['app/bundles/CoreBundle/Tests/Functional/RouteMapTest.php', 'app/tests/CoreBundle/Tests/Functional/RouteMapTest.php'],
    ],
];

foreach ($testFixes as $relativeFile => $fixes) {
    $file    = $root.'/'.$relativeFile;
    $content = loadFile($file);
    foreach ($fixes as [$search, $replace]) {
        $content = replaceOnce($content, $search, $replace, $relativeFile);
    }
    saveFile($file, $content);
    echo "fixed relative paths: {$relativeFile}\n";
}

// 7. regenerate composer autoloaders so the new autoload-dev entries take effect
passthru('composer dump-autoload', $exitCode);
if ($exitCode !== 0) {
    fail('composer dump-autoload failed');
}
echo "composer: regenerated autoloaders\n";

echo "\ndone\n";

/**
 * Rewrite app/bundles/*Bundle/Tests/ references to app/tests/*Bundle/Tests/.
 * Only the plural Tests directory is moved; the singular Test helper dir stays.
 */
function rewriteTestPaths(string $content): string
{
    return preg_replace(
        '#app/bundles/([A-Za-z]+Bundle)/Tests/#',
        'app/tests/$1/Tests/',
        $content
    );
}
