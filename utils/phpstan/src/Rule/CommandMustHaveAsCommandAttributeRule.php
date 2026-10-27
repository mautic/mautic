<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Every class that extends Symfony Command must declare the #[AsCommand] attribute.
 *
 * @implements Rule<Class_>
 */
final class CommandMustHaveAsCommandAttributeRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // an anonymous class carries no name to report, and is no registered command
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        // abstract base commands do not need the attribute
        if ($node->isAbstract()) {
            return [];
        }

        $className = (string) $node->namespacedName;
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        if (!$this->reflectionProvider->getClass($className)->is(Command::class)) {
            return [];
        }

        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (AsCommand::class === $attr->name->toString()) {
                    return [];
                }
            }
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Class "%s" extends Command but is missing the #[AsCommand] attribute.',
            $className
        ))
            ->identifier('mautic.asCommandAttribute')
            ->build();

        return [$ruleError];
    }
}
