<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller must not fetch a model by its string name, e.g. $this->getModel('lead').
 *
 * The string hides the real model class from static analysis and from the IDE, so the returned type is only the
 * generic MauticModelInterface. Injecting the model as a typed property gives the exact type and makes the
 * dependency visible.
 *
 * @implements Rule<MethodCall>
 */
final class NoGetModelWithStringInControllerRule implements Rule
{
    /**
     * @var string
     */
    private const GET_MODEL_METHOD = 'getModel';

    /**
     * @var string
     */
    private const CONTROLLER_SUFFIX = 'Controller.php';

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
        // inside a trait the scope file is the controller using it, so the very same call would be reported
        // once per controller, in a file that does not contain it
        if ($scope->isInTrait()) {
            return [];
        }

        if (!str_ends_with($scope->getFile(), self::CONTROLLER_SUFFIX)) {
            return [];
        }

        if (!$node->var instanceof Variable || 'this' !== $node->var->name) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if (self::GET_MODEL_METHOD !== $node->name->toString()) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return [];
        }

        if (!$firstArg->value instanceof String_) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Controller must not fetch the "%s" model by string. Inject the model as a typed property instead, to make the dependency and its type explicit.',
            $firstArg->value->value
        ))
            ->identifier('mautic.noGetModelWithStringInController')
            ->nonIgnorable()
            ->build();

        return [$ruleError];
    }
}
