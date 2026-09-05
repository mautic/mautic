<?php

declare(strict_types=1);

namespace Utils\PHPStan\Collector;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Collects the services of bundle Config/services.php files registered under a name of their own,
 * e.g. $services->set('mautic.campaign.membership.builder', MembershipBuilder::class).
 *
 * @implements Collector<MethodCall, array{string, string, int, int}>
 */
final class ServiceDefinitionNameCollector implements Collector
{
    private const string SERVICES_FILE_NAME = 'services.php';

    /**
     * The variable the services of a Config/services.php file are registered on. A $parameters->set() call
     * names a container parameter, never a service.
     */
    private const string SERVICES_VARIABLE_NAME = 'services';

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return array{string, string, int, int}|null the service id and the class name registered,
     *                                              with the first and the last line of the set() call
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return null;
        }

        if (!$node->name instanceof Identifier || 'set' !== $node->name->toString()) {
            return null;
        }

        if (!$node->var instanceof Variable || self::SERVICES_VARIABLE_NAME !== $node->var->name) {
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

        return [$args[0]->value->value, $className, $node->getStartLine(), $node->getEndLine()];
    }

    private function matchClassName(Node $classValue): ?string
    {
        if (!$classValue instanceof ClassConstFetch || !$classValue->class instanceof Name) {
            return null;
        }

        if (!$classValue->name instanceof Identifier || 'class' !== $classValue->name->toLowerString()) {
            return null;
        }

        return $classValue->class->toString();
    }
}
