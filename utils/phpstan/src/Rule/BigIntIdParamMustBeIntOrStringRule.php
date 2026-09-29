<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\ORM\EntityRepository;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * An "$id" param must be "int|string" when it identifies an entity whose id is an unsigned bigint via
 * "addBigIntIdField()", as Doctrine hydrates bigint as string.
 *
 * Covers repositories (entity taken from the generic type) and controllers (entity taken from the model/repository
 * the param is passed to, e.g. $this->leadModel->getEntity($leadId)).
 *
 * @implements Rule<ClassMethod>
 */
final readonly class BigIntIdParamMustBeIntOrStringRule implements Rule
{
    private const string ID_PARAM_NAME = 'id';

    private const string CONTROLLER_SUFFIX = 'Controller.php';

    public function __construct(
        private ReflectionProvider $reflectionProvider,
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
        if ($scope->isInTrait() || !$scope->isInClass()) {
            return [];
        }

        if ($this->isEntityRepository($scope)) {
            return $this->processRepositoryMethod($node, $scope);
        }

        if (str_ends_with($scope->getFile(), self::CONTROLLER_SUFFIX)) {
            return $this->processControllerMethod($node, $scope);
        }

        return [];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processRepositoryMethod(ClassMethod $classMethod, Scope $scope): array
    {
        $ruleErrors = [];

        foreach ($classMethod->params as $param) {
            if (!$param->var instanceof Variable || self::ID_PARAM_NAME !== $param->var->name) {
                continue;
            }

            // untyped params are left to type coverage
            if (null === $param->type || $this->isIntOrString($param->type)) {
                continue;
            }

            if (!$this->isBigIntIdEntityRepository($scope)) {
                return [];
            }

            $ruleErrors[] = $this->createRuleError($classMethod, (string) $param->var->name, $param->getStartLine());
        }

        return $ruleErrors;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function processControllerMethod(ClassMethod $classMethod, Scope $scope): array
    {
        $ruleErrors = [];

        foreach ($classMethod->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            // only "int"/"?int" params are wrong; untyped is left to type coverage, "int|string" is already correct
            if (!$this->isIntType($param->type)) {
                continue;
            }

            if (!$this->paramMapsToBigIntEntity($classMethod, $param->var->name, $scope)) {
                continue;
            }

            $ruleErrors[] = $this->createRuleError($classMethod, $param->var->name, $param->getStartLine());
        }

        return $ruleErrors;
    }

    private function createRuleError(ClassMethod $classMethod, string $paramName, int $line): \PHPStan\Rules\IdentifierRuleError
    {
        return RuleErrorBuilder::message(sprintf(
            'Param "$%s" of "%s()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
            $paramName,
            $classMethod->name->toString()
        ))
            ->identifier('mautic.bigIntIdParamMustBeIntOrString')
            ->line($line)
            ->build();
    }

    private function isIntOrString(Node $type): bool
    {
        if (!$type instanceof UnionType) {
            return false;
        }

        $typeNames = [];
        foreach ($type->types as $subType) {
            if (!$subType instanceof Identifier) {
                return false;
            }

            $typeNames[] = $subType->toLowerString();
        }

        return [] === array_diff(['int', 'string'], $typeNames) && [] === array_diff($typeNames, ['int', 'string', 'null']);
    }

    private function isIntType(?Node $type): bool
    {
        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        return $type instanceof Identifier && 'int' === $type->toLowerString();
    }

    private function paramMapsToBigIntEntity(ClassMethod $classMethod, string $paramName, Scope $scope): bool
    {
        $nodeFinder = new NodeFinder();

        /** @var MethodCall[] $methodCalls */
        $methodCalls = $nodeFinder->findInstanceOf($classMethod->stmts ?? [], MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (!$this->firstArgIsParam($methodCall, $paramName)) {
                continue;
            }

            $returnType = $this->resolveMethodCallReturnType($methodCall, $scope);
            if (!$returnType instanceof Type) {
                continue;
            }

            foreach (TypeCombinator::removeNull($returnType)->getObjectClassNames() as $className) {
                if ($this->isBigIntIdEntityClass($className)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function firstArgIsParam(MethodCall $methodCall, string $paramName): bool
    {
        // an entity id is the first argument of a lookup call, e.g. getEntity($id)/find($id)
        $firstArg = $methodCall->getArgs()[0] ?? null;

        return $firstArg instanceof Node\Arg
            && $firstArg->value instanceof Variable
            && $paramName === $firstArg->value->name;
    }

    private function resolveMethodCallReturnType(MethodCall $methodCall, Scope $scope): ?Type
    {
        if (!$methodCall->name instanceof Identifier) {
            return null;
        }

        // only "$this->property->method(...)" calls are resolvable from class reflection alone
        if (!$methodCall->var instanceof PropertyFetch
            || !$methodCall->var->var instanceof Variable
            || 'this' !== $methodCall->var->var->name
            || !$methodCall->var->name instanceof Identifier
        ) {
            return null;
        }

        $classReflection = $scope->getClassReflection();
        if (null === $classReflection) {
            return null;
        }

        $propertyName = $methodCall->var->name->toString();
        if (!$classReflection->hasInstanceProperty($propertyName)) {
            return null;
        }

        $callerType = $classReflection->getInstanceProperty($propertyName, $scope)->getReadableType();

        $methodName = $methodCall->name->toString();
        if (!$callerType->hasMethod($methodName)->yes()) {
            return null;
        }

        $methodReflection = $callerType->getMethod($methodName, $scope);

        $variants = $methodReflection->getVariants();

        return $variants[0]->getReturnType();
    }

    private function isEntityRepository(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        return null !== $classReflection && null !== $classReflection->getAncestorWithClassName(EntityRepository::class);
    }

    private function isBigIntIdEntityRepository(Scope $scope): bool
    {
        $entityRepositoryReflection = $scope->getClassReflection()->getAncestorWithClassName(EntityRepository::class);
        if (null === $entityRepositoryReflection) {
            return false;
        }

        $entityType = $entityRepositoryReflection->getActiveTemplateTypeMap()->getType('T');
        if (null === $entityType) {
            return false;
        }

        foreach ($entityType->getObjectClassNames() as $entityClassName) {
            if ($this->isBigIntIdEntityClass($entityClassName)) {
                return true;
            }
        }

        return false;
    }

    private function isBigIntIdEntityClass(string $entityClassName): bool
    {
        if (!$this->reflectionProvider->hasClass($entityClassName)) {
            return false;
        }

        $fileName = $this->reflectionProvider->getClass($entityClassName)->getFileName();
        if (null === $fileName) {
            return false;
        }

        // no-arg call maps the primary "id" field
        return 1 === preg_match('#->addBigIntIdField\(\s*\)#', (string) file_get_contents($fileName));
    }
}
