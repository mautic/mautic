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
 * Collects the aliases of bundle Config/services.php files that name a class after a service id,
 * e.g. $services->alias(DropzoneErrorHandler::class, 'mautic.asset.upload.error.handler').
 *
 * An alias the other way around, e.g. $services->alias('mautic.some.helper', SomeHelper::class), and an alias
 * of an interface to its implementation are left out, those are the shapes that carry their weight.
 *
 * @implements Collector<MethodCall, array{string, string, int}>
 */
final class ClassNameServiceAliasCollector implements Collector
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
     * @return array{string, string, int}|null the class name aliased and the service id it points at,
     *                                         with the line of the alias() call
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

        $className = $this->matchClassName($args[0]->value);
        if (null === $className || !$args[1]->value instanceof String_) {
            return null;
        }

        return [$className, $args[1]->value->value, $node->getStartLine()];
    }

    private function matchClassName(Node $aliasValue): ?string
    {
        if (!$aliasValue instanceof ClassConstFetch || !$aliasValue->class instanceof Name) {
            return null;
        }

        if (!$aliasValue->name instanceof Identifier || 'class' !== $aliasValue->name->toLowerString()) {
            return null;
        }

        return $aliasValue->class->toString();
    }
}
