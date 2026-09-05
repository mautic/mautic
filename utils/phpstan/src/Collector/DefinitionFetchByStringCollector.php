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
 * Collects the definition fetches that name a service by a string id, e.g. the compiler pass call
 * $container->getDefinition('mautic.schema.helper.column').
 *
 * Unlike ServiceDefinitionFetchCollector, which keeps only the id, this one keeps the line as well so the
 * rule can point at the very call.
 *
 * @implements Collector<MethodCall, array{string, int}>
 */
final class DefinitionFetchByStringCollector implements Collector
{
    /**
     * @var list<string>
     */
    private const array DEFINITION_METHOD_NAMES = ['getDefinition', 'hasDefinition', 'findDefinition', 'removeDefinition'];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return array{string, int}|null the service id fetched with the line the call is on
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (!$node->name instanceof Identifier || !in_array($node->name->toString(), self::DEFINITION_METHOD_NAMES, true)) {
            return null;
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg || !$firstArg->value instanceof String_) {
            return null;
        }

        return [$firstArg->value->value, $node->getStartLine()];
    }
}
