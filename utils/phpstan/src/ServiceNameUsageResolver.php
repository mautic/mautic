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
     * "mautic.integration.%1$s" of IntegrationHelper covers "mautic.integration.hubspot".
     *
     * A placeholder repeated under the same number stands for one and the same value, so "mautic.%1$s.%1$s"
     * covers "mautic.project.project" but never "mautic.asset.permissions".
     */
    private function isMatchingName(string $usedName, string $serviceName): bool
    {
        if ($usedName === $serviceName) {
            return true;
        }

        if (0 === preg_match_all('#%(\d+)\$s#', $usedName, $matches)) {
            return false;
        }

        $seenNumbers = [];
        $pattern = '';
        $offset = 0;

        foreach ($matches[0] as $matchIndex => $placeholder) {
            $placeholderOffset = strpos($usedName, $placeholder, $offset);
            $pattern .= preg_quote(substr($usedName, $offset, $placeholderOffset - $offset), '#');

            $placeholderNumber = $matches[1][$matchIndex];
            if (isset($seenNumbers[$placeholderNumber])) {
                $pattern .= '\\'.$seenNumbers[$placeholderNumber];
            } else {
                $seenNumbers[$placeholderNumber] = count($seenNumbers) + 1;
                $pattern .= '([a-zA-Z0-9_]+)';
            }

            $offset = $placeholderOffset + strlen($placeholder);
        }

        $pattern .= preg_quote(substr($usedName, $offset), '#');

        return 1 === preg_match('#^'.$pattern.'$#', $serviceName);
    }
}
