<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;
use Utils\PHPStan\Collector\ServiceTypeUsageCollector;

/**
 * Reports the service aliases of Config/services.php nothing refers to.
 *
 * A service id alias is used by an id string, e.g. service('mautic.some.helper'), a class name alias is used
 * by a type hint, as that is what autowiring wires it by.
 *
 * Only PHP is analysed, so an alias used by a Twig template, a YAML or an XML file alone looks unused here.
 *
 * @implements Rule<CollectedDataNode>
 */
final class NoUnusedServiceAliasRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    /**
     * @param CollectedDataNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $usedTypeNames = $this->resolveUsedTypeNames($node);

        $ruleErrors = [];

        /** @var array<string, list<array{string, string, int, int}>> $aliasesByFilePath */
        $aliasesByFilePath = $node->get(ServiceAliasCollector::class);

        /** @var array<string, list<array{string, int}>> $usagesByFilePath */
        $usagesByFilePath = $node->get(ServiceNameUsageCollector::class);

        foreach ($aliasesByFilePath as $filePath => $aliases) {
            foreach ($aliases as [$aliasName, , $startLine, $endLine]) {
                if (isset($usedTypeNames[ltrim($aliasName, '\\')])) {
                    continue;
                }

                if ($this->isUsedName($aliasName, $usagesByFilePath, $filePath, $startLine, $endLine)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service alias "%s" is never used, remove it.',
                    $aliasName
                ))
                    ->identifier('mautic.noUnusedServiceAlias')
                    ->file($filePath)
                    ->line($startLine)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @param array<string, list<array{string, int}>> $usagesByFilePath
     */
    private function isUsedName(string $aliasName, array $usagesByFilePath, string $aliasFilePath, int $startLine, int $endLine): bool
    {
        foreach ($usagesByFilePath as $filePath => $usages) {
            foreach ($usages as [$usedName, $line]) {
                if (!$this->isMatchingName($usedName, $aliasName)) {
                    continue;
                }

                // the alias() call names the alias itself, that is no usage of it
                if ($filePath === $aliasFilePath && $line >= $startLine && $line <= $endLine) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * A used name is either the alias name itself or the sprintf() format an id is built by at runtime,
     * e.g. "mautic.%s.model.%s" of ModelFactory covers "mautic.lead.model.lead".
     */
    private function isMatchingName(string $usedName, string $aliasName): bool
    {
        if ($usedName === $aliasName) {
            return true;
        }

        if (!str_contains($usedName, '%s')) {
            return false;
        }

        $pattern = str_replace(preg_quote('%s', '#'), '[a-z0-9_]+', preg_quote($usedName, '#'));

        return 1 === preg_match('#^'.$pattern.'$#', $aliasName);
    }

    /**
     * @return array<string, true>
     */
    private function resolveUsedTypeNames(CollectedDataNode $collectedDataNode): array
    {
        $usedTypeNames = [];

        /** @var array<string, list<list<string>>> $typeUsagesByFilePath */
        $typeUsagesByFilePath = $collectedDataNode->get(ServiceTypeUsageCollector::class);

        foreach ($typeUsagesByFilePath as $typeUsages) {
            foreach ($typeUsages as $typeNames) {
                foreach ($typeNames as $typeName) {
                    $usedTypeNames[ltrim($typeName, '\\')] = true;
                }
            }
        }

        return $usedTypeNames;
    }
}
