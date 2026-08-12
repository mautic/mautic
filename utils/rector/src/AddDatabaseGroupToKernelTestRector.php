<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Rector\AbstractRector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Marks every test that boots the Symfony kernel with #[Group('database')].
 *
 *   final class LeadControllerTest extends MauticMysqlTestCase
 *   ->
 *   #[Group('database')]
 *   final class LeadControllerTest extends MauticMysqlTestCase
 *
 * The group is what lets CI split the suite: the database job runs --group database, the job
 * without a database service runs --exclude-group database. PHPUnit does not inherit class
 * attributes from a parent class, so the attribute has to sit on every concrete test class.
 *
 * Left alone:
 *   - tests that already carry the attribute
 *   - abstract base test cases, which PHPUnit never runs on their own
 */
final class AddDatabaseGroupToKernelTestRector extends AbstractRector
{
    private const GROUP_ATTRIBUTE = 'PHPUnit\Framework\Attributes\Group';

    private const DATABASE_GROUP = 'database';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        // the namespace owns both the class and the use imports, the bare class covers the rare
        // test file that declares no namespace at all
        return [Namespace_::class, Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Namespace_) {
            return $this->refactorNamespace($node);
        }

        if ($node instanceof Class_) {
            return $this->refactorClassWithoutNamespace($node);
        }

        return null;
    }

    private function refactorNamespace(Namespace_ $namespace): ?Namespace_
    {
        $classesToMark = [];

        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof Class_) {
                continue;
            }

            if (!$this->isKernelBootingTest($stmt)) {
                continue;
            }

            $classesToMark[] = $stmt;
        }

        if ([] === $classesToMark) {
            return null;
        }

        // a handful of tests already import another "Group" class, e.g. a Mautic entity
        $canImportGroup = !$this->hasConflictingGroupImport($namespace);
        $attributeName  = $canImportGroup ? new Name('Group') : new FullyQualified(self::GROUP_ATTRIBUTE);

        foreach ($classesToMark as $class) {
            $class->attrGroups[] = $this->createGroupAttributeGroup($attributeName);
        }

        if ($canImportGroup) {
            $this->addGroupUseImport($namespace);
        }

        return $namespace;
    }

    private function refactorClassWithoutNamespace(Class_ $class): ?Class_
    {
        // classes inside a namespace are handled through the namespace node, where the import goes
        if ($class->namespacedName instanceof Name && $class->namespacedName->isQualified()) {
            return null;
        }

        if (!$this->isKernelBootingTest($class)) {
            return null;
        }

        // there is no namespace to hang an import on, so the attribute spells out the full name
        $class->attrGroups[] = $this->createGroupAttributeGroup(new FullyQualified(self::GROUP_ATTRIBUTE));

        return $class;
    }

    private function createGroupAttributeGroup(Name $attributeName): AttributeGroup
    {
        $groupAttribute = new Attribute($attributeName, [new Arg(new String_(self::DATABASE_GROUP))]);

        return new AttributeGroup([$groupAttribute]);
    }

    private function hasConflictingGroupImport(Namespace_ $namespace): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof Use_) {
                continue;
            }

            foreach ($stmt->uses as $useItem) {
                if ($this->isName($useItem->name, self::GROUP_ATTRIBUTE)) {
                    continue;
                }

                $shortName = $useItem->alias instanceof Node\Identifier
                    ? $useItem->alias->toString()
                    : $useItem->name->getLast();

                if ('Group' === $shortName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isKernelBootingTest(Class_ $class): bool
    {
        // an abstract base test case is never run on its own, the concrete child carries the group
        if ($class->isAbstract()) {
            return false;
        }

        if (!$class->name instanceof Node\Identifier) {
            return false;
        }

        if ($this->hasDatabaseGroupAttribute($class)) {
            return false;
        }

        // a test file without a namespace declaration carries no namespacedName
        $className = (string) ($class->namespacedName ?? $class->name);
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        return $this->reflectionProvider->getClass($className)->is(KernelTestCase::class);
    }

    private function hasDatabaseGroupAttribute(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                if (!$this->isName($attribute->name, self::GROUP_ATTRIBUTE)) {
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

    private function addGroupUseImport(Namespace_ $namespace): void
    {
        $lastUseKey    = null;
        $firstLaterKey = null;

        foreach ($namespace->stmts as $key => $stmt) {
            if (!$stmt instanceof Use_) {
                continue;
            }

            foreach ($stmt->uses as $useItem) {
                if ($this->isName($useItem->name, self::GROUP_ATTRIBUTE)) {
                    return;
                }

                // the coding standard sorts imports alphabetically, so the new one slots in
                if (null === $firstLaterKey && strcasecmp($useItem->name->toString(), self::GROUP_ATTRIBUTE) > 0) {
                    $firstLaterKey = $key;
                }
            }

            $lastUseKey = $key;
        }

        $groupUse = new Use_([new UseItem(new Name(self::GROUP_ATTRIBUTE))]);

        // keep the imports together, otherwise the file starts with the new import
        $insertKey = $firstLaterKey ?? (null === $lastUseKey ? 0 : $lastUseKey + 1);
        array_splice($namespace->stmts, $insertKey, 0, [$groupUse]);
    }
}
