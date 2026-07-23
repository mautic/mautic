<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor service dependency must not be nullable.
 *
 * A service is always provided by the container, so "?SomeService $service" or "SomeService|null $service" only hides
 * the fact that it is really required. Scalar/array nullables are left alone - those are real optional values.
 *
 * @implements Rule<ClassMethod>
 */
final class NoNullableServiceInConstructorRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ('__construct' !== $node->name->toLowerString()) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            $serviceType = $this->matchNullableServiceType($param->type);
            if (null === $serviceType) {
                continue;
            }

            $parameterName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? '$'.$param->var->name
                : '';

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Constructor service "%s" of type "%s" is nullable. A service is always provided, make it non-nullable.',
                $parameterName,
                $serviceType
            ))
                ->identifier('mautic.noNullableServiceInConstructor')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * Returns the service class name when the type is a nullable class type, null otherwise.
     */
    private function matchNullableServiceType(Node\Identifier|Name|ComplexType|null $type): ?string
    {
        if ($type instanceof NullableType) {
            return $type->type instanceof Name ? $type->type->toString() : null;
        }

        if ($type instanceof UnionType) {
            $className = null;
            $hasNull = false;

            foreach ($type->types as $unionedType) {
                if ($unionedType instanceof Node\Identifier && 'null' === $unionedType->toLowerString()) {
                    $hasNull = true;

                    continue;
                }

                if ($unionedType instanceof Name) {
                    $className = $unionedType->toString();
                }
            }

            return $hasNull ? $className : null;
        }

        return null;
    }
}
