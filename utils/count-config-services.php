<?php

declare(strict_types=1);
$rootDirectory = dirname(__DIR__);

require $rootDirectory.'/vendor/autoload.php';

// counts service definitions left in bundle Config/config.php files, per bundle
// usage: php utils/count-config-services.php [--groups]
$showGroups = in_array('--groups', $argv, true);

$configFilePaths = array_merge(
    glob($rootDirectory.'/app/bundles/*/Config/config.php') ?: [],
    glob($rootDirectory.'/plugins/*/Config/config.php') ?: [],
);

$countsByBundle = [];

foreach ($configFilePaths as $configFilePath) {
    $config = require $configFilePath;
    if (!is_array($config) || !isset($config['services']) || !is_array($config['services'])) {
        continue;
    }

    $bundleName = basename(dirname($configFilePath, 2));

    $countsByGroup = [];
    foreach ($config['services'] as $group => $services) {
        if (!is_array($services) || [] === $services) {
            continue;
        }

        $countsByGroup[$group] = count($services);
    }

    if ([] === $countsByGroup) {
        continue;
    }

    $countsByBundle[$bundleName] = $countsByGroup;
}

uasort($countsByBundle, fn (array $a, array $b): int => array_sum($b) <=> array_sum($a));

$total = 0;

printf("%-40s %s\n", 'BUNDLE', 'SERVICES');
printf("%s\n", str_repeat('-', 60));

foreach ($countsByBundle as $bundleName => $countsByGroup) {
    $bundleTotal = array_sum($countsByGroup);
    $total += $bundleTotal;

    printf("%-40s %d\n", $bundleName, $bundleTotal);

    if (!$showGroups) {
        continue;
    }

    arsort($countsByGroup);
    foreach ($countsByGroup as $group => $count) {
        printf("    %-36s %d\n", $group, $count);
    }
}

printf("%s\n", str_repeat('-', 60));
printf("%-40s %d\n", sprintf('TOTAL (%d bundles)', count($countsByBundle)), $total);
