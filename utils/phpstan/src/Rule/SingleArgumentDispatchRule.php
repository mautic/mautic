<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Mautic\CoreBundle\CoreEvents;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Dispatch CoreBundle events by the event object alone: $dispatcher->dispatch($event).
 *
 * Since Symfony 4.3 the event class name is the event name, so the string event name passed as the
 * second argument is redundant. Passing it also keeps the legacy CoreEvents:: constants alive. Every
 * CoreBundle event class maps to exactly one event name, so dropping the second argument is safe.
 *
 * @see https://symfony.com/blog/new-in-symfony-4-3-simpler-event-dispatching
 *
 * @implements Rule<MethodCall>
 */
final class SingleArgumentDispatchRule implements Rule
{
    private const string DISPATCH_METHOD = 'dispatch';

    private const string ERROR_MESSAGE = 'Dispatch the event object alone: ->dispatch($event). The event class is the event name (Symfony 4.3+), so drop the CoreEvents::%s second argument.';

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
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (self::DISPATCH_METHOD !== $node->name->toString()) {
            return [];
        }

        $args = $node->getArgs();
        if (count($args) < 2) {
            return [];
        }

        $secondArg = $args[1]->value;
        if (!$secondArg instanceof ClassConstFetch) {
            return [];
        }

        if (!$secondArg->class instanceof Name) {
            return [];
        }

        $constantName = $secondArg->name instanceof Identifier ? $secondArg->name->toString() : '';

        $ruleError = RuleErrorBuilder::message(sprintf(self::ERROR_MESSAGE, $constantName))
            ->identifier('mautic.singleArgumentDispatch')
            ->build();

        return [$ruleError];
    }
}
