<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A container definition fetch that names a class by a plain string should use the class constant instead,
 * e.g. $container->getDefinition('Mautic\CoreBundle\Helper\ColumnSchemaHelper') should pass
 * ColumnSchemaHelper::class. The string is only flagged when it is a real class name, a service id string
 * such as 'mautic.helper.core' is left alone.
 *
 * @implements Rule<MethodCall>
 */
final readonly class PreferClassInDefinitionFetchRule implements Rule
{
    /**
     * @var list<string>
     */
    private const array DEFINITION_METHOD_NAMES = ['getDefinition', 'hasDefinition', 'findDefinition', 'removeDefinition'];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

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
        if (!$node->name instanceof Identifier || !in_array($node->name->toString(), self::DEFINITION_METHOD_NAMES, true)) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg || !$firstArg->value instanceof String_) {
            return [];
        }

        $className = $firstArg->value->value;
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Fetch the definition by class constant, %s::class, rather than the string "%s".',
            $className,
            $className
        ))
            ->identifier('mautic.preferClassInDefinitionFetch')
            ->line($firstArg->getStartLine())
            ->build();

        return [$ruleError];
    }
}
