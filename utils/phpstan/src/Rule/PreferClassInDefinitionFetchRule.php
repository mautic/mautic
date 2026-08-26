<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Utils\PHPStan\Collector\ClassNameServiceAliasCollector;
use Utils\PHPStan\Collector\ClassTargetServiceAliasCollector;
use Utils\PHPStan\Collector\DefinitionFetchByStringCollector;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;

/**
 * Reports the definition fetches of compiler passes that name a service by a string id a class is known for,
 * e.g. $container->getDefinition('mautic.schema.helper.column') while the service carries the
 * ColumnSchemaHelper class in its registration.
 *
 * The class name says the same without the loose string:
 *
 *     $container->getDefinition('mautic.schema.helper.column')->setArgument('$prefix', $prefix);
 *
 *     $container->getDefinition(ColumnSchemaHelper::class)->setArgument('$prefix', $prefix);
 *
 * A class is known for an id registered by set('id', Class::class) or bridged by an alias either way, i.e.
 * alias('id', Class::class) or alias(Class::class, 'id').
 *
 * The fix is safe only where the class name is the definition getDefinition() reaches, a string-primary
 * service keeps the string until the registration is flipped to the class - getDefinition() does not resolve
 * an alias. An id nothing registers with a class, e.g. a legacy 'mautic.tblprefix_subscriber', is left alone,
 * there is no type to name it by.
 *
 * @implements Rule<CollectedDataNode>
 */
final class PreferClassInDefinitionFetchRule implements Rule
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

        /** @var array<string, list<array{string, int}>> $fetchesByFilePath */
        $fetchesByFilePath = $node->get(DefinitionFetchByStringCollector::class);

        $ruleErrors = [];

        foreach ($fetchesByFilePath as $filePath => $fetches) {
            foreach ($fetches as [$serviceId, $line]) {
                $className = $classNamesByServiceId[$serviceId] ?? null;
                if (null === $className) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Fetch the definition by its class, getDefinition(%s::class), rather than by the string id "%s" the service is registered with.',
                    $className,
                    $serviceId
                ))
                    ->identifier('mautic.preferClassInDefinitionFetch')
                    ->file($filePath)
                    ->line($line)
                    ->build();
            }
        }

        return $ruleErrors;
    }

    /**
     * @return array<string, string> the class name known for every string service id
     */
    private function resolveClassNamesByServiceId(CollectedDataNode $collectedDataNode): array
    {
        $classNamesByServiceId = [];

        /** @var array<string, list<array{string, string, int, int}>> $definitionsByFilePath */
        $definitionsByFilePath = $collectedDataNode->get(ServiceDefinitionNameCollector::class);
        foreach ($definitionsByFilePath as $definitions) {
            foreach ($definitions as [$serviceId, $className]) {
                $classNamesByServiceId[$serviceId] = $className;
            }
        }

        /** @var array<string, list<array{string, string, int}>> $targetAliasesByFilePath */
        $targetAliasesByFilePath = $collectedDataNode->get(ClassTargetServiceAliasCollector::class);
        foreach ($targetAliasesByFilePath as $aliases) {
            foreach ($aliases as [$serviceId, $className]) {
                $classNamesByServiceId[$serviceId] = $className;
            }
        }

        /** @var array<string, list<array{string, string, int}>> $nameAliasesByFilePath */
        $nameAliasesByFilePath = $collectedDataNode->get(ClassNameServiceAliasCollector::class);
        foreach ($nameAliasesByFilePath as $aliases) {
            foreach ($aliases as [$className, $serviceId]) {
                $classNamesByServiceId[$serviceId] = $className;
            }
        }

        return $classNamesByServiceId;
    }
}
