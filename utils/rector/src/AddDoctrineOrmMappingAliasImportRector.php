<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use Rector\Rector\AbstractRector;

/**
 * Adds "use Doctrine\ORM\Mapping as ORM;" to files that use ORM\ mapping attributes but miss the import.
 *
 * The loadMetadata to attribute conversion emits ORM\-prefixed attributes; a class that had no ORM
 * import before conversion would otherwise reference an undefined alias.
 */
final class AddDoctrineOrmMappingAliasImportRector extends AbstractRector
{
    private const string ORM_ALIAS = 'ORM';

    private const string ORM_NAMESPACE = 'Doctrine\\ORM\\Mapping';

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

        if (!$this->usesOrmAlias($node) || $this->alreadyImportsOrmAlias($node)) {
            return null;
        }

        $ormUse = new Use_([new UseItem(new Name(self::ORM_NAMESPACE), new Identifier(self::ORM_ALIAS))]);

        $firstUseIndex = null;
        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof Use_) {
                $firstUseIndex ??= $index;
            }
        }

        array_splice($node->stmts, $firstUseIndex ?? 0, 0, [$ormUse]);

        return $node;
    }

    private function usesOrmAlias(Namespace_ $namespace): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof ClassLike) {
                continue;
            }

            if ($this->hasOrmAttribute($stmt->attrGroups)) {
                return true;
            }

            foreach ($stmt->stmts as $member) {
                if (($member instanceof Property || $member instanceof ClassMethod)
                    && $this->hasOrmAttribute($member->attrGroups)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    private function hasOrmAttribute(array $attrGroups): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (str_starts_with($attr->name->toString(), self::ORM_ALIAS.'\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function alreadyImportsOrmAlias(Namespace_ $namespace): bool
    {
        foreach ($namespace->stmts as $stmt) {
            if (!$stmt instanceof Use_) {
                continue;
            }

            foreach ($stmt->uses as $use) {
                if ($use->alias instanceof Identifier
                    && self::ORM_ALIAS === $use->alias->toString()
                    && self::ORM_NAMESPACE === $use->name->toString()
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
