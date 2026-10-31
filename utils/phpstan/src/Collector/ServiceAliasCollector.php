<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the service aliases registered in bundle Config/services.php files,
 * e.g. $services->alias('mautic.some.helper', SomeHelper::class).
 *
 * @implements Collector<MethodCall, array{string, string, int, int}>
 */
final class ServiceAliasCollector implements Collector
{
    /**
     * @var string
     */
    private const SERVICES_FILE_NAME = 'services.php';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return array{string, string, int, int}|null the alias name and the service it points at,
     *                                              with the first and the last line of the alias() call
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return null;
        }

        if (!$node->name instanceof Identifier || 'alias' !== $node->name->toString()) {
            return null;
        }

        $args = $node->getArgs();
        if (2 !== count($args)) {
            return null;
        }

        $aliasName = $this->matchAliasName($args[0]->value);
        $aliasTarget = $this->matchAliasName($args[1]->value);
        if (null === $aliasName || null === $aliasTarget) {
            return null;
        }

        return [$aliasName, $aliasTarget, $node->getStartLine(), $node->getEndLine()];
    }

    /**
     * An alias is named either by a service id string or by a class name, e.g. SomeHelper::class.
     */
    private function matchAliasName(Node $aliasValue): ?string
    {
        if ($aliasValue instanceof String_) {
            return $aliasValue->value;
        }

        if (!$aliasValue instanceof ClassConstFetch || !$aliasValue->class instanceof Name) {
            return null;
        }

        if (!$aliasValue->name instanceof Identifier || 'class' !== $aliasValue->name->toLowerString()) {
            return null;
        }

        return $aliasValue->class->toString();
    }
}
