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
 * Collects the aliases of bundle Config/services.php files that point a string service id at a class,
 * e.g. $services->alias('mautic.helper.core_parameters', CoreParametersHelper::class).
 *
 * Such an id names the very same service its class name does, so a reference by the class name says the same
 * without the loose string. The alias the other way around, e.g. $services->alias(SomeHelper::class, 'id'),
 * is left to ClassNameServiceAliasCollector.
 *
 * @implements Collector<MethodCall, array{string, string, int}>
 */
final class ClassTargetServiceAliasCollector implements Collector
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
     * @return array{string, string, int}|null the string service id, the class name it points at and the
     *                                         line of the alias() call
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

        if (!$args[0]->value instanceof String_) {
            return null;
        }

        $className = $this->matchClassName($args[1]->value);
        if (null === $className) {
            return null;
        }

        return [$args[0]->value->value, $className, $node->getStartLine()];
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
