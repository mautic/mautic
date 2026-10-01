<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller action param named after a bigint entity id (e.g. "$leadId", "$contactId") must be "int|string",
 * as Doctrine hydrates an unsigned bigint id as string. Covers the cases the data-flow rule cannot trace, such as
 * ids used only as query filter values or passed to dynamic method calls.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class ControllerBigIntIdParamByNameRule implements Rule
{
    /**
     * @var string[]
     */
    private const array BIGINT_ID_PARAM_NAMES = ['leadId', 'contactId'];

    private const string CONTROLLER_SUFFIX = 'Controller.php';

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

        if (!str_ends_with($scope->getFile(), self::CONTROLLER_SUFFIX)) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (!in_array($param->var->name, self::BIGINT_ID_PARAM_NAMES, true)) {
                continue;
            }

            // only "int"/"?int" params are wrong; untyped is left to type coverage, "int|string" is already correct
            if (!$this->isIntType($param->type)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Param "$%s" of "%s()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                $param->var->name,
                $node->name->toString()
            ))
                ->identifier('mautic.controllerBigIntIdParamByName')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isIntType(?Node $type): bool
    {
        if ($type instanceof NullableType) {
            $type = $type->type;
        }

        return $type instanceof Identifier && 'int' === $type->toLowerString();
    }
}
