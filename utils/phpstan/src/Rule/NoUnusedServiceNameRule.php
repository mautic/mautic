<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;
use Utils\PHPStan\ServiceNameUsageResolver;

/**
 * Reports the services of Config/services.php registered under a name nothing asks for,
 * e.g. $services->set('mautic.campaign.membership.builder', MembershipBuilder::class).
 *
 * Such a name buys nothing, the class name alone registers the very same service:
 * $services->set(MembershipBuilder::class).
 *
 * A name a class name alias points at is left alone by itself, as the alias() call spells the name out and
 * so counts as a usage of it.
 *
 * A model id, e.g. "mautic.lead.model.list", is left alone as well, as ModelFactory builds it at runtime.
 *
 * Only PHP is analysed, so a name used by a Twig template, a YAML or an XML file alone looks unused here.
 *
 * @implements Rule<CollectedDataNode>
 */
final readonly class NoUnusedServiceNameRule implements Rule
{
    /**
     * The "mautic.%s.model.%s" format ModelFactory builds a model container id by.
     *
     * @see \Mautic\CoreBundle\Factory\ModelFactory::getModel()
     *
     * @var string
     */
    private const MODEL_ID_PATTERN = '#^mautic\.[a-zA-Z0-9_]+\.model\.[a-zA-Z0-9_]+$#';

    public function __construct(
        private ServiceNameUsageResolver $serviceNameUsageResolver,
    ) {
    }

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

        /** @var array<string, list<array{string, string, int, int}>> $definitionsByFilePath */
        $definitionsByFilePath = $node->get(ServiceDefinitionNameCollector::class);

        /** @var array<string, list<array{string, int}>> $usagesByFilePath */
        $usagesByFilePath = $node->get(ServiceNameUsageCollector::class);

        foreach ($definitionsByFilePath as $filePath => $definitions) {
            $classNameCounts = $this->resolveClassNameCounts($definitions);

            foreach ($definitions as [$serviceName, $className, $startLine, $endLine]) {
                if (1 === preg_match(self::MODEL_ID_PATTERN, $serviceName)) {
                    continue;
                }

                // two services of the same class would collide under the very same class name id
                if (1 !== $classNameCounts[$className]) {
                    continue;
                }

                if ($this->serviceNameUsageResolver->isUsedName($serviceName, $usagesByFilePath, $filePath, $startLine, $endLine)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Service name "%s" is never used, register the service by its class name instead - $services->set(%s::class).',
                    $serviceName,
                    $className
                ))
                    ->identifier('mautic.noUnusedServiceName')
                    ->file($filePath)
                    ->line($startLine)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @param list<array{string, string, int, int}> $definitions
     *
     * @return array<string, int>
     */
    private function resolveClassNameCounts(array $definitions): array
    {
        $classNameCounts = [];

        foreach ($definitions as [, $className]) {
            $classNameCounts[$className] = ($classNameCounts[$className] ?? 0) + 1;
        }

        return $classNameCounts;
    }
}
