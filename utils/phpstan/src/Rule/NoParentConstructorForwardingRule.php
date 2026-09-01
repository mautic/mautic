<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor must not repeat the parameters of its parent only to hand them straight over.
 *
 * A child that adds one dependency of its own ends up spelling out every parent parameter again:
 *
 *     public function __construct(
 *         ManagerRegistry $doctrine,
 *         ModelFactory $modelFactory,
 *         // ... every other parent parameter
 *         private readonly AuthorizationCheckerInterface $authorizationChecker,
 *     ) {
 *         parent::__construct($doctrine, $modelFactory, ...);
 *     }
 *
 * Every parameter the parent adds later has to be added here as well, in the right order, in every child.
 * Only a long list counts, from 10 parameters up - handing one or two over is no bother. A constructor
 * that takes about as many dependencies of its own is a constructor in its own right, so the own ones
 * must be at most half of the handed over ones.
 * An autowire*() method takes the new dependency alone and leaves the parent constructor untouched:
 *
 *     #[Required]
 *     public function autowireSomeController(AuthorizationCheckerInterface $authorizationChecker): void
 *     {
 *         $this->authorizationChecker = $authorizationChecker;
 *     }
 *
 * @implements Rule<ClassMethod>
 */
final class NoParentConstructorForwardingRule implements Rule
{
    /**
     * @var string
     */
    private const CONSTRUCTOR_NAME = '__construct';

    /**
     * A parameter or two handed over is no bother, a long list repeated in every child is.
     *
     * @var int
     */
    private const MINIMAL_PASSED_THROUGH_PARAM_COUNT = 6;

    /**
     * Classes 3rd-party code extends, so their constructor contract must stay as it is.
     *
     * @var string[]
     */
    private const SKIPPED_CLASSES = [
        'Mautic\ApiBundle\Controller\CommonApiController',
    ];

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::CONSTRUCTOR_NAME !== $node->name->toLowerString()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection instanceof ClassReflection && in_array($classReflection->getName(), self::SKIPPED_CLASSES, true)) {
            return [];
        }

        $forwardedVariableNames = $this->resolveForwardedVariableNames($node);
        if ([] === $forwardedVariableNames) {
            return [];
        }

        $passedThroughParams = $this->resolvePassedThroughParams($node, $forwardedVariableNames);
        if (count($passedThroughParams) < self::MINIMAL_PASSED_THROUGH_PARAM_COUNT) {
            return [];
        }

        if ((2 * count($this->resolveOwnParams($node, $forwardedVariableNames))) > count($passedThroughParams)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Constructor hands %d parameter(s) straight over to parent::__construct(), drop them and take the dependency of its own in an autowire*() method instead.',
                count($passedThroughParams)
            ))
                ->identifier('mautic.noParentConstructorForwarding')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * The variables a sole parent::__construct() call hands over, e.g. "doctrine" of parent::__construct($doctrine).
     *
     * @return array<string, true>
     */
    private function resolveForwardedVariableNames(ClassMethod $classMethod): array
    {
        $stmts = $classMethod->stmts ?? [];
        if (1 !== count($stmts)) {
            return [];
        }

        $onlyStmt = $stmts[0];
        if (!$onlyStmt instanceof Expression || !$onlyStmt->expr instanceof StaticCall) {
            return [];
        }

        $staticCall = $onlyStmt->expr;
        if (!$staticCall->class instanceof Name || 'parent' !== $staticCall->class->toLowerString()) {
            return [];
        }

        if (!$staticCall->name instanceof Identifier || self::CONSTRUCTOR_NAME !== $staticCall->name->toLowerString()) {
            return [];
        }

        $forwardedVariableNames = [];
        foreach ($staticCall->getArgs() as $arg) {
            if ($arg->value instanceof Variable && is_string($arg->value->name)) {
                $forwardedVariableNames[$arg->value->name] = true;
            }
        }

        return $forwardedVariableNames;
    }

    /**
     * A parameter the class keeps for itself, e.g. the promoted property of a child that adds one dependency.
     *
     * @param array<string, true> $forwardedVariableNames
     *
     * @return list<Param>
     */
    private function resolveOwnParams(ClassMethod $classMethod, array $forwardedVariableNames): array
    {
        $ownParams = [];

        foreach ($classMethod->params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (isset($forwardedVariableNames[$param->var->name])) {
                continue;
            }

            $ownParams[] = $param;
        }

        return $ownParams;
    }

    /**
     * A parameter is passed through when it is handed to the parent and kept nowhere else, so a promoted
     * property, the very dependency of the class itself, is left out.
     *
     * @param array<string, true> $forwardedVariableNames
     *
     * @return list<Param>
     */
    private function resolvePassedThroughParams(ClassMethod $classMethod, array $forwardedVariableNames): array
    {
        $passedThroughParams = [];

        foreach ($classMethod->params as $param) {
            if (0 !== $param->flags) {
                continue;
            }

            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if (!isset($forwardedVariableNames[$param->var->name])) {
                continue;
            }

            $passedThroughParams[] = $param;
        }

        return $passedThroughParams;
    }
}
