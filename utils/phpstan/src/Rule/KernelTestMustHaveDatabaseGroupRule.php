<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Every test that boots the Symfony kernel must declare #[Group('database')].
 *
 * CI splits the test run on this group: the job with a database service runs --group database,
 * the job without one runs --exclude-group database. A kernel test missing the attribute lands
 * in the job that has no database and fails there.
 *
 * @implements Rule<Class_>
 */
final class KernelTestMustHaveDatabaseGroupRule implements Rule
{
    private const GROUP_ATTRIBUTE = 'PHPUnit\Framework\Attributes\Group';

    private const DATABASE_GROUP = 'database';

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
        // an anonymous class carries no name to report, and PHPUnit never runs it as a test
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        // an abstract base test case is never run on its own, the concrete child carries the group
        if ($node->isAbstract()) {
            return [];
        }

        $className = (string) $node->namespacedName;
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        if (!$this->reflectionProvider->getClass($className)->is(KernelTestCase::class)) {
            return [];
        }

        if ($this->hasDatabaseGroupAttribute($node)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Class "%s" boots the kernel but is missing the #[Group(\'database\')] attribute.',
            $className
        ))
            ->identifier('mautic.databaseGroupAttribute')
            ->build();

        return [$ruleError];
    }

    private function hasDatabaseGroupAttribute(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (self::GROUP_ATTRIBUTE !== $attribute->name->toString()) {
                    continue;
                }

                $firstArg = $attribute->args[0] ?? null;
                if (!$firstArg instanceof Arg) {
                    continue;
                }

                if ($firstArg->value instanceof String_ && self::DATABASE_GROUP === $firstArg->value->value) {
                    return true;
                }
            }
        }

        return false;
    }
}
