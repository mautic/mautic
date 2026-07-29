<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Every class that extends Symfony Command must declare the #[AsCommand] attribute.
 *
 * @implements Rule<InClassNode>
 */
final class CommandMustHaveAsCommandAttributeRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if ($classReflection->isAnonymous()) {
            return [];
        }

        // abstract base commands do not need the attribute
        if ($classReflection->isAbstract()) {
            return [];
        }

        if (!$classReflection->is(Command::class)) {
            return [];
        }

        if ($this->hasAsCommandAttribute($node->getOriginalNode())) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Class "%s" extends Command but is missing the #[AsCommand] attribute.',
            $classReflection->getName()
        ))
            ->identifier('mautic.asCommandAttribute')
            ->build();

        return [$ruleError];
    }

    private function hasAsCommandAttribute(Node\Stmt\ClassLike $classLike): bool
    {
        foreach ($classLike->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (AsCommand::class === $attr->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }
}
