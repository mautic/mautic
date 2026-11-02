<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;

/**
 * A "@param"/"@return" docblock typed as an array (e.g. "Foo[]", "array") or "iterable" must be
 * backed by a native "array"/"iterable" type declaration. A missing or "mixed" native type is reported.
 *
 * @implements Rule<ClassMethod>
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
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
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

        // an overridden method cannot freely change its native param/return types without breaking
        // the parent/interface signature, so adding a type is left to the method that declares it
        if ($this->overridesParentMethod($classReflection, $node->name->toString())) {
            return [];
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection?->getName(),
            $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
            $node->name->toString(),
            $docComment->getText()
        );

        $ruleErrors = [];

        $paramTags = $resolvedPhpDoc->getParamTags();
        foreach ($node->params as $param) {
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
                'Param "$%s" of "%s()" has a "%s" docblock, so it must declare the native "%s" type.',
                $param->var->name,
                $node->name->toString(),
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
                    'Method "%s()" has a "%s" return docblock, so it must declare the native "%s" return type.',
                    $node->name->toString(),
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

    private function overridesParentMethod(?ClassReflection $classReflection, string $methodName): bool
    {
        if (!$classReflection instanceof ClassReflection) {
            return false;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === $classReflection->getName()) {
                continue;
            }

            if ($ancestor->hasMethod($methodName)) {
                return true;
            }
        }

        return false;
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
        // "Foo[]" / "array" / "array<...>" resolve to an array type
        if ($type->isArray()->yes()) {
            return 'array';
        }

        // the "iterable" pseudo-type only; a concrete Traversable object (Generator, Collection) is skipped
        if ($type->isIterable()->yes() && !$type->isObject()->yes()) {
            return 'iterable';
        }

        return null;
    }
}
