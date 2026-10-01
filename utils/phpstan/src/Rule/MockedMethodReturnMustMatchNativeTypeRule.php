<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Value passed to mocked "->method('x')->willReturn()" must match native return type of "x()".
 *
 * @implements Rule<MethodCall>
 */
final readonly class MockedMethodReturnMustMatchNativeTypeRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || 'willReturn' !== $node->name->toString()) {
            return [];
        }

        $methodCall = $this->findMethodCall($node->var);
        if (!$methodCall instanceof MethodCall) {
            return [];
        }

        $mockedMethodArg = $methodCall->getArgs()[0]->value ?? null;
        if (!$mockedMethodArg instanceof String_) {
            return [];
        }

        $mockedMethodName = $mockedMethodArg->value;
        $mockType         = $scope->getType($this->resolveMockExpr($methodCall->var));

        $ruleErrors = [];
        foreach ($mockType->getObjectClassReflections() as $classReflection) {
            if (in_array($classReflection->getName(), [MockObject::class, Stub::class], true)) {
                continue;
            }

            if (!$classReflection->hasNativeMethod($mockedMethodName)) {
                continue;
            }

            $nativeReturnType = $this->resolveStaticType($classReflection->getNativeMethod($mockedMethodName)
                ->getVariants()[0]
                ->getNativeReturnType());

            foreach ($node->getArgs() as $arg) {
                $returnValueType = $scope->getType($arg->value);
                if (!$this->isMismatch($nativeReturnType, $returnValueType)) {
                    continue;
                }

                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Mocked method "%s::%s()" returns "%s", but "%s" is passed to willReturn().',
                    $classReflection->getName(),
                    $mockedMethodName,
                    $nativeReturnType->describe(VerbosityLevel::typeOnly()),
                    $returnValueType->describe(VerbosityLevel::typeOnly())
                ))
                    ->identifier('mautic.mockedMethodReturnMustMatchNativeType')
                    ->build();
            }
        }

        return $ruleErrors;
    }

    private function isMismatch(Type $nativeReturnType, Type $returnValueType): bool
    {
        // compare mocked class only, mocks of final classes are not subtypes of them
        if ((new ObjectType(Stub::class))->isSuperTypeOf($returnValueType)->yes()) {
            $mockedClassNames = array_diff($returnValueType->getObjectClassNames(), [MockObject::class, Stub::class]);
            if ([] === $mockedClassNames) {
                return false;
            }

            $returnValueType = TypeCombinator::union(...array_map(
                static fn (string $className): ObjectType => new ObjectType($className),
                array_values($mockedClassNames)
            ));
        }

        return $nativeReturnType->isSuperTypeOf($returnValueType)->no();
    }

    private function resolveStaticType(Type $type): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof StaticType) {
                return $type->getStaticObjectType();
            }

            return $traverse($type);
        });
    }

    private function findMethodCall(Expr $expr): ?MethodCall
    {
        while ($expr instanceof MethodCall) {
            if ($expr->name instanceof Identifier && 'method' === $expr->name->toString()) {
                return $expr;
            }

            $expr = $expr->var;
        }

        return null;
    }

    private function resolveMockExpr(Expr $expr): Expr
    {
        if ($expr instanceof MethodCall && $expr->name instanceof Identifier && 'expects' === $expr->name->toString()) {
            return $expr->var;
        }

        return $expr;
    }
}
