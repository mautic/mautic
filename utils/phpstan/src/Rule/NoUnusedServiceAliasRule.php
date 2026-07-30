<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceModelKeyUsageCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;

/**
 * Reports the service id aliases of Config/services.php nothing refers to,
 * e.g. $services->alias('mautic.some.helper', SomeHelper::class) no service('mautic.some.helper') asks for.
 *
 * A class name alias, e.g. $services->alias(SomeHelper::class, 'mautic.some.helper'), is left alone on purpose.
 * It is not a mere lookup shortcut: it replaces the definition the PSR-4 $services->load() registers under the
 * very same class name id, so dropping it brings that autowired definition back to life - a second service of
 * the same class, tagged again by autoconfigure(), or a container that no longer compiles.
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
        $ruleErrors = [];

        /** @var array<string, list<array{string, int, int}>> $aliasesByFilePath */
        $aliasesByFilePath = $node->get(ServiceAliasCollector::class);

        $usagesByFilePath = $this->resolveUsagesByFilePath($node);

        foreach ($aliasesByFilePath as $filePath => $aliases) {
            foreach ($aliases as [$aliasName, $startLine, $endLine]) {
                if (str_contains($aliasName, '\\')) {
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
     * @return array<string, list<array{string, int}>> the service ids used, by the file path they are used in
     */
    private function resolveUsagesByFilePath(CollectedDataNode $collectedDataNode): array
    {
        /** @var array<string, list<array{string, int}>> $usagesByFilePath */
        $usagesByFilePath = $collectedDataNode->get(ServiceNameUsageCollector::class);

        /** @var array<string, list<array{string, int}>> $modelUsagesByFilePath */
        $modelUsagesByFilePath = $collectedDataNode->get(ServiceModelKeyUsageCollector::class);

        foreach ($modelUsagesByFilePath as $filePath => $modelUsages) {
            foreach ($modelUsages as $modelUsage) {
                $usagesByFilePath[$filePath][] = $modelUsage;
            }
        }

        return $usagesByFilePath;
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
     * A used name is either the alias name itself or the format an id is built by at runtime, e.g. the
     * "mautic.%s.model.%s" of ModelFactory covers "mautic.lead.model.lead".
     */
    private function isMatchingName(string $usedName, string $aliasName): bool
    {
        if ($usedName === $aliasName) {
            return true;
        }

        if (!str_contains($usedName, '%s')) {
            return false;
        }

        $pattern = str_replace(preg_quote('%s', '#'), '[a-zA-Z0-9_]+', preg_quote($usedName, '#'));

        return 1 === preg_match('#^'.$pattern.'$#', $aliasName);
    }
}
