<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the service ids a compiler pass reaches for as a definition of its own,
 * e.g. $container->getDefinition('mautic.http.client').
 *
 * Such an id has to stay a definition, an alias in its place makes the container builder fail with
 * "You have requested a non-existent service".
 *
 * @implements Collector<MethodCall, string>
 */
final class ServiceDefinitionFetchCollector implements Collector
{
    /**
     * @var list<string>
     */
    private const array DEFINITION_METHOD_NAMES = ['getDefinition', 'hasDefinition', 'findDefinition', 'removeDefinition'];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): ?string
    {
        if (!$node->name instanceof Identifier || !in_array($node->name->toString(), self::DEFINITION_METHOD_NAMES, true)) {
            return null;
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg || !$firstArg->value instanceof String_) {
            return null;
        }

        return $firstArg->value->value;
    }
}
