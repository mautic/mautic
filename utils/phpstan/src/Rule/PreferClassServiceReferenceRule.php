<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ClassTargetServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceStringReferenceCollector;

/**
 * Reports the service() references of Config/services.php files that ask for a service by a string id an
 * alias already points at the very class of, e.g. service('mautic.helper.core_parameters') while
 * $services->alias('mautic.helper.core_parameters', CoreParametersHelper::class) is registered.
 *
 * The class name names the very same service, so the reference says the same by the type instead:
 *
 *     $services->alias('mautic.helper.core_parameters', CoreParametersHelper::class);
 *     ...
 *     ->args([service('mautic.helper.core_parameters')]);
 *
 *     ->args([service(CoreParametersHelper::class)]);
 *
 * Once every reference names the class, the string alias nothing else asks for falls to NoUnusedServiceAliasRule.
 *
 * @implements Rule<CollectedDataNode>
 */
final class PreferClassServiceReferenceRule implements Rule
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
        $classNamesByServiceId = $this->resolveClassNamesByServiceId($node);

        /** @var array<string, list<array{string, int}>> $referencesByFilePath */
        $referencesByFilePath = $node->get(ServiceStringReferenceCollector::class);

        $ruleErrors = [];

        foreach ($referencesByFilePath as $filePath => $references) {
            foreach ($references as [$serviceId, $line]) {
                $className = $classNamesByServiceId[$serviceId] ?? null;
                if (null === $className) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Reference the service by its class, service(%s::class), rather than by the string id "%s" a class name alias already covers.',
                    $className,
                    $serviceId
                ))
                    ->identifier('mautic.preferClassServiceReference')
                    ->file($filePath)
                    ->line($line)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @return array<string, string> the class name every string service id is aliased to
     */
    private function resolveClassNamesByServiceId(CollectedDataNode $collectedDataNode): array
    {
        /** @var array<string, list<array{string, string, int}>> $aliasesByFilePath */
        $aliasesByFilePath = $collectedDataNode->get(ClassTargetServiceAliasCollector::class);

        $classNamesByServiceId = [];

        foreach ($aliasesByFilePath as $aliases) {
            foreach ($aliases as [$serviceId, $className]) {
                $classNamesByServiceId[$serviceId] = $className;
            }
        }

        return $classNamesByServiceId;
    }
}
