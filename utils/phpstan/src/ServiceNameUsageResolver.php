<?php

declare(strict_types=1);

namespace Utils\PHPStan;

/**
 * Tells whether a service id of a Config/services.php file is asked for anywhere.
 *
 * Shared by the rules that report the service ids nothing refers to, be it an alias() name or a set() name.
 */
final class ServiceNameUsageResolver
{
    /**
     * @param string                                  $serviceName      the service id registered
     * @param array<string, list<array{string, int}>> $usagesByFilePath the service ids used, by file path
     * @param string                                  $servicesFilePath the file the service id is registered in
     * @param int                                     $startLine        the first line of the registering call
     * @param int                                     $endLine          the last line of the registering call
     */
    public function isUsedName(string $serviceName, array $usagesByFilePath, string $servicesFilePath, int $startLine, int $endLine): bool
    {
        foreach ($usagesByFilePath as $filePath => $usages) {
            foreach ($usages as [$usedName, $line]) {
                if (!$this->isMatchingName($usedName, $serviceName)) {
                    continue;
                }

                // the registering call names the service itself, that is no usage of it
                if ($filePath === $servicesFilePath && $line >= $startLine && $line <= $endLine) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * A used name is either the service id itself or the format an id is built by at runtime, e.g. the
     * "mautic.integration.%s" of IntegrationHelper covers "mautic.integration.hubspot".
     */
    private function isMatchingName(string $usedName, string $serviceName): bool
    {
        if ($usedName === $serviceName) {
            return true;
        }

        if (!str_contains($usedName, '%s')) {
            return false;
        }

        if ($this->isTooGenericFormat($usedName)) {
            return false;
        }

        $pattern = str_replace(preg_quote('%s', '#'), '[a-zA-Z0-9_]+', preg_quote($usedName, '#'));

        return 1 === preg_match('#^'.$pattern.'$#', $serviceName);
    }

    /**
     * A format naming the vendor prefix alone, e.g. the "mautic.%s.%s" of a translation key built out of an
     * entity type, covers every service id of the same shape and would mark them all used. Such a format
     * tells nothing about the very service asked for, so it covers no service id at all here.
     */
    private function isTooGenericFormat(string $usedName): bool
    {
        $literalSegments = array_filter(
            explode('.', $usedName),
            static fn (string $segment): bool => !str_contains($segment, '%s')
        );

        return count($literalSegments) < 2;
    }
}
