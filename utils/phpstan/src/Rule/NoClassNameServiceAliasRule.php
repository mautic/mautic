<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ClassNameServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceDefinitionFetchCollector;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;

/**
 * Reports the aliases of Config/services.php that name a class after a Mautic service id,
 * e.g. $services->alias(DropzoneErrorHandler::class, 'mautic.asset.upload.error.handler').
 *
 * Such an alias only patches up a set() call that named the service by hand. Registering the service by its
 * class name in the first place says the same in one line:
 *
 *     $services->set('mautic.asset.upload.error.handler', DropzoneErrorHandler::class);
 *     $services->alias(DropzoneErrorHandler::class, 'mautic.asset.upload.error.handler');
 *
 *     $services->set(DropzoneErrorHandler::class);
 *
 * A service id still asked for by name keeps its own alias, the other way around:
 *
 *     $services->set(DropzoneErrorHandler::class);
 *     $services->alias('mautic.asset.upload.error.handler', DropzoneErrorHandler::class);
 *
 * Only a Mautic service id the very same class is registered under counts. An alias bridging a class to a
 * service of another class is no duplicate at all, e.g. ClientInterface::class to "mautic.http.client",
 * and neither is an alias to a vendor service, e.g. BuildContainer::class to "lightsaml.container.build".
 *
 * A service id a compiler pass fetches by getDefinition() keeps its set() name as well, an alias in its place
 * makes the container builder fail.
 *
 * @implements Rule<CollectedDataNode>
 */
final class NoClassNameServiceAliasRule implements Rule
{
    private const string MAUTIC_SERVICE_ID_PREFIX = 'mautic.';

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

        /** @var array<string, list<array{string, string, int}>> $aliasesByFilePath */
        $aliasesByFilePath = $node->get(ClassNameServiceAliasCollector::class);

        /** @var array<string, list<array{string, string, int, int}>> $definitionsByFilePath */
        $definitionsByFilePath = $node->get(ServiceDefinitionNameCollector::class);

        $fetchedDefinitionNames = $this->resolveFetchedDefinitionNames($node);

        foreach ($aliasesByFilePath as $filePath => $aliases) {
            $classNamesByServiceName = $this->resolveClassNamesByServiceName($definitionsByFilePath[$filePath] ?? []);

            foreach ($aliases as [$className, $serviceName, $line]) {
                if (!str_starts_with($serviceName, self::MAUTIC_SERVICE_ID_PREFIX)) {
                    continue;
                }

                if (($classNamesByServiceName[$serviceName] ?? null) !== $className) {
                    continue;
                }

                if (isset($fetchedDefinitionNames[$serviceName])) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service alias of "%s" to "%s" brings no value, register the service by its class name instead - $services->set(%s::class).',
                    $className,
                    $serviceName,
                    $className
                ))
                    ->identifier('mautic.noClassNameServiceAlias')
                    ->file($filePath)
                    ->line($line)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @return array<string, true> the service ids a compiler pass fetches as a definition
     */
    private function resolveFetchedDefinitionNames(CollectedDataNode $collectedDataNode): array
    {
        $fetchedDefinitionNames = [];

        /** @var array<string, list<string>> $fetchesByFilePath */
        $fetchesByFilePath = $collectedDataNode->get(ServiceDefinitionFetchCollector::class);

        foreach ($fetchesByFilePath as $fetches) {
            foreach ($fetches as $serviceName) {
                $fetchedDefinitionNames[$serviceName] = true;
            }
        }

        return $fetchedDefinitionNames;
    }

    /**
     * @param list<array{string, string, int, int}> $definitions
     *
     * @return array<string, string> the class name every service id is registered with
     */
    private function resolveClassNamesByServiceName(array $definitions): array
    {
        $classNamesByServiceName = [];

        foreach ($definitions as [$serviceName, $className]) {
            $classNamesByServiceName[$serviceName] = $className;
        }

        return $classNamesByServiceName;
    }
}
