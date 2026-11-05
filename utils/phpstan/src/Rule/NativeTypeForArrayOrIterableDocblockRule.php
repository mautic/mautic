<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * A "@param"/"@return" docblock typed as an array (e.g. "Foo[]", "array") or "iterable" must be
 * backed by a native "array"/"iterable" type declaration. A missing or "mixed" native type is reported.
 * Applies to methods, plain functions and closures.
 *
 * @implements Rule<FunctionLike>
 *
 * @see \Utils\PHPStan\Tests\Rule\NativeTypeForArrayOrIterableDocblockRuleTest
 */
final readonly class NativeTypeForArrayOrIterableDocblockRule implements Rule
{
    public function __construct(
        private FileTypeMapper $fileTypeMapper
    ) {
    }

    public function getNodeType(): string
    {
        return FunctionLike::class;
    }

    /**
     * @param FunctionLike $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        if (null === $docComment) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        // a bare name ("handle") is needed to look up parents; the display name ("handle()",
        // "{closure}") is only used in the reported message
        $methodName = $node instanceof ClassMethod || $node instanceof Function_ ? $node->name->toString() : null;
        $displayName = $this->resolveDisplayName($node, $methodName);

        // an overridden method cannot freely change its native param/return types without breaking
        // the parent/interface signature, so adding a type is left to the method that declares it
        if ($node instanceof ClassMethod && null !== $methodName && $this->overridesParentMethod($classReflection, $methodName)) {
            return [];
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection?->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $this->resolveDocBlockFunctionName($node),
            $docComment->getText()
        );

        $ruleErrors = [];

        $paramTags = $resolvedPhpDoc->getParamTags();
        foreach ($node->getParams() as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            // variadic "...$args" collects into an array; the docblock type describes a single item
            if ($param->variadic) {
                continue;
            }

            // a by-reference param often accepts broader runtime types than the docblock (e.g. a Traversable where an array is documented), so a native array/iterable type is unsafe
            if ($param->byRef) {
                continue;
            }

            if (!$this->isMissingOrMixed($param->type)) {
                continue;
            }

            if (!isset($paramTags[$param->var->name])) {
                continue;
            }

            $requiredType = $this->resolveRequiredNativeType($paramTags[$param->var->name]->getType());
            if (null === $requiredType) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s" has a "%s" docblock, so it must declare the native "%s" type.',
                $param->var->name,
                $displayName,
                $requiredType,
                $requiredType
            ))
                ->identifier('mautic.nativeTypeForArrayOrIterableDocblock')
                ->line($param->getStartLine())
                ->build();
        }

        $returnTag = $resolvedPhpDoc->getReturnTag();
        if (null !== $returnTag && $this->isMissingOrMixed($node->getReturnType())) {
            $requiredType = $this->resolveRequiredNativeType($returnTag->getType());
            if (null !== $requiredType) {
                $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                    'Method "%s" has a "%s" return docblock, so it must declare the native "%s" return type.',
                    $displayName,
                    $requiredType,
                    $requiredType
                ))
                    ->identifier('mautic.nativeTypeForArrayOrIterableDocblock')
                    ->line($node->getStartLine())
                    ->build();
            }
        }

        return $ruleErrors;
    }

    // the docblock resolver keys a method by its bare name (the class provides the scope) but a plain
    // function by its fully-qualified name; a closure has neither
    private function resolveDocBlockFunctionName(FunctionLike $functionLike): ?string
    {
        if ($functionLike instanceof ClassMethod) {
            return $functionLike->name->toString();
        }

        if ($functionLike instanceof Function_) {
            return null !== $functionLike->namespacedName
                ? $functionLike->namespacedName->toString()
                : $functionLike->name->toString();
        }

        return null;
    }

    private function resolveDisplayName(FunctionLike $functionLike, ?string $methodName): string
    {
        if (null !== $methodName) {
            return $methodName.'()';
        }

        if ($functionLike instanceof ArrowFunction) {
            return 'fn()';
        }

        return '{closure}';
    }

    private function overridesParentMethod(?ClassReflection $classReflection, string $methodName): bool
    {
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        $isConstructor = '__construct' === strtolower($methodName);

        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName()) {
                continue;
            }

            if (!$ancestor->hasNativeMethod($methodName)) {
                continue;
            }

            $ancestorMethod = $ancestor->getNativeMethod($methodName);

            // a private ancestor method imposes no signature contract on the child
            if ($ancestorMethod->isPrivate()) {
                continue;
            }

            // PHP exempts "__construct" from signature compatibility (LSP) only against a concrete
            // parent constructor; an interface or abstract constructor is a binding contract
            if ($isConstructor && !$this->isAbstractMethod($ancestorMethod)) {
                continue;
            }

            // a concrete trait method is copied into the using class, so it binds nothing on its own;
            // an abstract trait method still constrains the signature, and a parent-trait method stays
            // bound through the parent class ancestor
            if ($ancestor->isTrait() && !$this->isAbstractMethod($ancestorMethod)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function isAbstractMethod(ExtendedMethodReflection $method): bool
    {
        $isAbstract = $method->isAbstract();

        return $isAbstract instanceof TrinaryLogic ? $isAbstract->yes() : $isAbstract;
    }

    private function isMissingOrMixed(?Node $type): bool
    {
        if (!$type instanceof Node) {
            return true;
        }

        return $type instanceof Identifier && 'mixed' === strtolower($type->toString());
    }

    private function resolveRequiredNativeType(Type $type): ?string
    {
        // a "|null" member widens the required native type to its nullable form
        $prefix = TypeCombinator::containsNull($type) ? '?' : '';
        $withoutNull = TypeCombinator::removeNull($type);

        // "Foo[]" / "array" / "array<...>" (and a "Foo[]|Bar[]" union of only arrays) resolve to an array type
        if ($withoutNull->isArray()->yes()) {
            return $prefix.'array';
        }

        // the "iterable" pseudo-type only; a concrete Traversable object (Generator, Collection) is skipped
        if ($withoutNull->isIterable()->yes() && !$withoutNull->isObject()->yes()) {
            return $prefix.'iterable';
        }

        return null;
    }
}
