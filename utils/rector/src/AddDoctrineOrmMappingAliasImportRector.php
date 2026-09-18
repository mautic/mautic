<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use Rector\Rector\AbstractRector;

/**
 * Adds `use Doctrine\ORM\Mapping as ORM;` when a class carries ORM\-prefixed attributes but the
 * alias is missing. The loadMetadata-to-attribute rules emit the ORM\ prefix; most entities already
 * import the alias, this covers the few that do not.
 */
final class AddDoctrineOrmMappingAliasImportRector extends AbstractRector
{
    private const string MAPPING_NAMESPACE = 'Doctrine\\ORM\\Mapping';

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Namespace_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Namespace_) {
            return null;
        }

        if (!$this->hasDanglingOrmAttribute($node) || $this->hasOrmAliasImport($node)) {
            return null;
        }

        $use = new Use_([new UseItem(new Name(self::MAPPING_NAMESPACE), new Identifier('ORM'))]);

        $insertAt = 0;
        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof Use_) {
                $insertAt = $index + 1;
            }
        }

        array_splice($node->stmts, $insertAt, 0, [$use]);

        return $node;
    }

    /**
     * With no `use ... as ORM`, a relative `#[ORM\Column]` resolves against the current namespace to
     * `<namespace>\ORM\Column` - a class that does not exist. That dangling prefix is the signal the
     * alias import is missing.
     */
    private function hasDanglingOrmAttribute(Namespace_ $namespace): bool
    {
        if (!$namespace->name instanceof Name) {
            return false;
        }

        $danglingPrefix = $namespace->name->toString().'\\ORM\\';

        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof Class_) {
                continue;
            }

            foreach ($this->classAttributes($stmt) as $attribute) {
                if (str_starts_with($attribute->name->toString(), $danglingPrefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return iterable<Attribute>
     */
    private function classAttributes(Class_ $class): iterable
    {
        $holders = [$class, ...$class->getProperties(), ...$class->getMethods()];

        foreach ($holders as $holder) {
            foreach ($holder->attrGroups as $attrGroup) {
                yield from $attrGroup->attrs;
            }
        }
    }

    private function hasOrmAliasImport(Namespace_ $namespace): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof Use_) {
                continue;
            }

            foreach ($stmt->uses as $use) {
                if ($use->alias instanceof Identifier
                    && 'ORM' === $use->alias->toString()
                    && self::MAPPING_NAMESPACE === $use->name->toString()
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
