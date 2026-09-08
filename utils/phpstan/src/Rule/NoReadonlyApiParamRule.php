<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A promoted constructor property marked "@api" in its @param docblock is part of the public API and
 * may be swapped in tests via reflection, so it must not be readonly.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class NoReadonlyApiParamRule implements Rule
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
        $docComment = $node->getDocComment();
        if (!$docComment instanceof \PhpParser\Comment\Doc) {
            return [];
        }

        $docText = $docComment->getText();

        $ruleErrors = [];
        foreach ($node->params as $param) {
            if (!$param->isReadonly()) {
                continue;
            }

            if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
                continue;
            }

            $paramName = $param->var->name;
            if (!$this->hasApiParamAnnotation($docText, $paramName)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Parameter "$%s" is marked "@api" and may be swapped via reflection in tests, so it must not be readonly. Remove the readonly modifier.',
                $paramName
            ))
                ->identifier('mautic.noReadonlyApiParam')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function hasApiParamAnnotation(string $docText, string $paramName): bool
    {
        foreach (explode("\n", $docText) as $line) {
            if (!str_contains($line, '@param')) {
                continue;
            }

            if (!str_contains($line, '$'.$paramName)) {
                continue;
            }

            if (str_contains($line, '@api')) {
                return true;
            }
        }

        return false;
    }
}
