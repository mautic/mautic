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
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor service dependency must not be nullable.
 *
 * A service is always provided by the container, so "?SomeService $service" or "SomeService|null $service" only hides
 * the fact that it is really required. Non-service nullables are left alone: scalars/arrays, date value objects, and
 * exceptions (whose "$previous" is nullable by PHP convention). Data-holder classes living in an entity, event, DTO,
 * message, DAO, token or value object namespace are skipped whole - those constructors carry values, not services.
 *
 * @implements Rule<ClassMethod>
 */
final class NoNullableServiceInConstructorRule implements Rule
{
    /**
     * @var string[]
     */
    private const SKIPPED_NAMESPACE_PARTS = ['\\Entity\\', '\\Event\\', '\\DTO\\', '\\Message\\', '\\DAO\\', '\\Token\\', '\\Exception\\', '\\Helper\\', '\\ValueObject\\'];

    /**
     * @var string[]
     */
    private const SKIPPED_CLASS_TYPES = [
        'Symfony\\Component\\Security\\Http\\Authenticator\\Passport\\Badge\\BadgeInterface',
        'Symfony\\Component\\Form\\FormTypeInterface',
    ];

    /**
     * @var string[]
     */
    private const SKIPPED_EXACT_CLASSES = [\Mautic\CoreBundle\IpLookup\AbstractLookup::class];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

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

        // an anonymous class is a local one-off, not a container service
        $classReflection = $scope->getClassReflection();
        if (null === $classReflection || $classReflection->isAnonymous()) {
            return [];
        }

        // a data-holder namespace (entity, event, DTO, ...) carries values, not services
        if ($this->isSkippedNamespace($classReflection->getName())) {
            return [];
        }

        if (in_array($classReflection->getName(), self::SKIPPED_EXACT_CLASSES, true)) {
            return [];
        }

        // a data-holder class type (e.g. a security badge) carries values, not services
        foreach (self::SKIPPED_CLASS_TYPES as $skippedClassType) {
            if ($classReflection->is($skippedClassType)) {
                return [];
            }
        }

        $paramTypes = $this->resolveParamClassTypes($node, $scope);

        $ruleErrors = [];

        foreach ($node->params as $param) {
            $serviceName = $this->matchNullableServiceName($param->type);
            if (!$serviceName instanceof Name) {
                continue;
            }

            $serviceType = $scope->resolveName($serviceName);
            if ($this->isValueObjectType($serviceType)) {
                continue;
            }

            // a sibling param of the same type already provides it non-nullable, so this one is a real optional extra
            if (count(array_keys($paramTypes, $serviceType, true)) > 1) {
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
     * Resolves every constructor param to its class-type name (nullable or not), for duplicate-type detection.
     *
     * @return string[]
     */
    private function resolveParamClassTypes(ClassMethod $classMethod, Scope $scope): array
    {
        $paramTypes = [];

        foreach ($classMethod->params as $param) {
            $className = $this->matchClassName($param->type);
            if ($className instanceof Name) {
                $paramTypes[] = $scope->resolveName($className);
            }
        }

        return $paramTypes;
    }

    /**
     * Returns the class-type name node of any type, nullable or not, null when the type is not a class type.
     */
    private function matchClassName(Node\Identifier|Name|ComplexType|null $type): ?Name
    {
        if ($type instanceof Name) {
            return $type;
        }

        return $this->matchNullableServiceName($type);
    }

    /**
     * Returns the class-type name node when the type is a nullable class type, null otherwise.
     */
    private function matchNullableServiceName(Node\Identifier|Name|ComplexType|null $type): ?Name
    {
        if ($type instanceof NullableType) {
            return $type->type instanceof Name ? $type->type : null;
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
                    $className = $unionedType;
                }
            }

            return $hasNull ? $className : null;
        }

        return null;
    }

    private function isSkippedNamespace(string $className): bool
    {
        foreach (self::SKIPPED_NAMESPACE_PARTS as $skippedNamespacePart) {
            if (str_contains($className, $skippedNamespacePart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A nullable class type that is not really a service: an exception ("$previous") or a date value object.
     */
    private function isValueObjectType(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->is(\Throwable::class) || $classReflection->is(\DateTimeInterface::class);
    }
}
