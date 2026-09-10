<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;

/**
 * Converts the static mapping helpers in loadMetadata (self::addUuidField, self::addProjectsField,
 * ...) into Doctrine attributes. Native ClassMetadataBuilder calls are left behind in a trimmed
 * loadMetadata for LoadMetadataToDoctrineAttributeRector. A class is left untouched when no static
 * helper could be converted.
 *
 * When both rules are configured together, order this one first: it treats a class that already
 * carries ORM attributes as hybrid and then leaves addProjectsField behind, so it must see the
 * pre-attribute class to emit the projects property.
 */
final class LoadMetadataStaticHelperToAttributeRector extends AbstractRector
{
    private bool $isHybrid = false;

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Class_) {
            return null;
        }

        $loadMetadata = $node->getMethod('loadMetadata');
        if (!$loadMetadata instanceof ClassMethod || null === $loadMetadata->stmts) {
            return null;
        }

        // A hybrid class already maps its own fields via attributes, so a property-emitting helper
        // is left behind; only known no-op helpers drop out of loadMetadata.
        $this->isHybrid = $this->hasOrmMappingAttribute($node->attrGroups);

        $builderAssignStatement = null;
        $keptStatements         = [];
        $newProperties          = [];
        $anyConverted           = false;

        foreach ($loadMetadata->stmts as $stmt) {
            $converted = $this->convertStatement($stmt);

            if ('assign' === $converted) {
                $builderAssignStatement = $stmt;

                continue;
            }

            if ('drop' === $converted) {
                $anyConverted = true;

                continue;
            }

            if ($converted instanceof Property) {
                $newProperties[] = $converted;
                $anyConverted    = true;

                continue;
            }

            // Native builder calls and anything not understood stay in loadMetadata.
            $keptStatements[] = $stmt;
        }

        // Nothing understood: leave the file untouched.
        if (!$anyConverted) {
            return null;
        }

        if ([] === $keptStatements) {
            // Everything converted: drop loadMetadata entirely.
            $node->stmts = array_values(array_filter(
                $node->stmts,
                static fn (Node $stmt): bool => $stmt !== $loadMetadata
            ));
        } else {
            // Native builder calls stay in a trimmed loadMetadata for the attribute rule; keep their builder.
            $loadMetadata->stmts = array_values(array_filter([$builderAssignStatement, ...$keptStatements]));
        }

        // Properties whose declaration lives in a trait get emitted into the class body so their
        // per-entity mapping can be attached; place them after trait uses.
        if ([] !== $newProperties) {
            $insertAt = 0;
            foreach ($node->stmts as $index => $stmt) {
                if ($stmt instanceof TraitUse) {
                    $insertAt = $index + 1;
                }
            }

            array_splice($node->stmts, $insertAt, 0, $newProperties);
        }

        return $node;
    }

    /**
     * Convert a single loadMetadata statement: 'assign' for the builder assignment, 'drop' for a
     * no-op helper to remove, a Property for addProjectsField, or null to keep the statement.
     */
    private function convertStatement(Node\Stmt $stmt): string|Property|null
    {
        if (!$stmt instanceof Expression) {
            return null;
        }

        $expr = $stmt->expr;

        // $builder = new ClassMetadataBuilder($metadata);
        if ($expr instanceof Expr\Assign) {
            if ($expr->var instanceof Variable && $this->isName($expr->var, 'builder') && $expr->expr instanceof New_) {
                return 'assign';
            }

            return null;
        }

        if (!$expr instanceof StaticCall) {
            return null;
        }

        if ($this->isHybrid) {
            return $this->isNoOpHelper($expr) ? 'drop' : null;
        }

        // self::addProjectsField($builder, $table, $column): emit a standalone projects property.
        $projectsProperty = $this->tryProjectsField($expr);
        if ($projectsProperty instanceof Property) {
            return $projectsProperty;
        }

        return $this->isNoOpHelper($expr) ? 'drop' : null;
    }

    /**
     * These helpers map trait properties (UuidTrait::$uuid, OptimisticLockTrait::$version,
     * TranslationEntityTrait, VariantEntityTrait, DynamicContentEntityTrait) that carry their own
     * mapping attributes. The properties are not in the class body, so no per-entity attribute is
     * emitted; the call simply drops out of loadMetadata.
     */
    private function isNoOpHelper(StaticCall $call): bool
    {
        if (!$call->class instanceof Name || !in_array($call->class->toString(), ['self', 'static'], true)) {
            return false;
        }

        if (!$call->name instanceof Identifier) {
            return false;
        }

        $noOpHelpers = ['addUuidField', 'addVersionField', 'addTranslationMetadata', 'addVariantMetadata', 'addDynamicContentMetadata'];

        return in_array($call->name->toString(), $noOpHelpers, true);
    }

    /**
     * self::addProjectsField($builder, $tableName, $columnName) maps the trait-declared
     * `projects` ManyToMany with a per-entity join table. Emit it as a standalone property.
     */
    private function tryProjectsField(StaticCall $call): ?Property
    {
        if (!$call->class instanceof Name || !in_array($call->class->toString(), ['self', 'static'], true)) {
            return null;
        }

        if (!$call->name instanceof Identifier || 'addProjectsField' !== $call->name->toString()) {
            return null;
        }

        // args: ($builder, $tableName, $columnName)
        $tableName  = $this->stringFromArg($call->args, 1);
        $columnName = $this->stringFromArg($call->args, 2);
        if (null === $tableName || null === $columnName) {
            return null;
        }

        $target = new ClassConstFetch(new FullyQualified('Mautic\\ProjectBundle\\Entity\\Project'), new Identifier('class'));

        $attributeGroups = $this->manyToManyAttributes(
            $target,
            'name',
            new Array_([new ArrayItem(new String_('ASC'), new String_('name'))]),
            $tableName,
            [$this->joinColumn($columnName, 'id', false, false, 'CASCADE')],
            [$this->joinColumn('project_id', 'id', false, false, 'CASCADE')],
        );

        return new Property(
            Modifiers::PRIVATE,
            [new PropertyItem('projects')],
            [],
            new FullyQualified('Doctrine\\Common\\Collections\\Collection'),
            $attributeGroups,
        );
    }

    /**
     * @param list<array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}> $joinColumns
     * @param list<array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}> $inverseJoinColumns
     *
     * @return list<AttributeGroup>
     */
    private function manyToManyAttributes(
        Expr $target,
        string $indexBy,
        Array_ $orderBy,
        string $joinTable,
        array $joinColumns,
        array $inverseJoinColumns,
    ): array {
        $args = [
            $this->namedArg('targetEntity', $target),
            $this->namedArg('cascade', new Array_(array_map(
                static fn (string $c): ArrayItem => new ArrayItem(new String_($c)),
                ['merge', 'persist', 'detach'],
            ))),
            $this->namedArg('fetch', new String_('LAZY')),
            $this->namedArg('indexBy', new String_($indexBy)),
        ];

        $attributes = [$this->attribute('ManyToMany', $args)];

        $attributes[] = $this->attribute('JoinTable', [$this->namedArg('name', new String_($joinTable))]);

        foreach ($joinColumns as $joinColumn) {
            $attributes[] = $this->joinColumnAttribute($joinColumn);
        }

        foreach ($inverseJoinColumns as $inverseJoinColumn) {
            $attributes[] = $this->joinColumnAttribute($inverseJoinColumn, 'InverseJoinColumn');
        }

        $attributes[] = $this->attribute('OrderBy', [new Arg($orderBy)]);

        return $attributes;
    }

    /**
     * @return array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}
     */
    private function joinColumn(string $name, string $ref, bool $nullable, bool $unique, ?string $onDelete): array
    {
        return ['name' => $name, 'ref' => $ref, 'nullable' => $nullable, 'unique' => $unique, 'onDelete' => $onDelete];
    }

    /**
     * @param array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string} $joinColumn
     */
    private function joinColumnAttribute(array $joinColumn, string $shortName = 'JoinColumn'): AttributeGroup
    {
        $args = [$this->namedArg('name', new String_($joinColumn['name']))];

        if ('id' !== $joinColumn['ref']) {
            $args[] = $this->namedArg('referencedColumnName', new String_($joinColumn['ref']));
        }

        // JoinColumn defaults to nullable: true, so only the false case needs stating.
        if (!$joinColumn['nullable']) {
            $args[] = $this->namedArg('nullable', new ConstFetch(new Name('false')));
        }

        if ($joinColumn['unique']) {
            $args[] = $this->namedArg('unique', new ConstFetch(new Name('true')));
        }

        if (null !== $joinColumn['onDelete']) {
            $args[] = $this->namedArg('onDelete', new String_($joinColumn['onDelete']));
        }

        return $this->attribute($shortName, $args);
    }

    /**
     * @param Arg[] $args
     */
    private function attribute(string $shortName, array $args): AttributeGroup
    {
        return new AttributeGroup([new Attribute(new Name('ORM\\'.$shortName), array_values($args))]);
    }

    private function namedArg(string $name, Expr $value): Arg
    {
        return new Arg($value, false, false, [], new Identifier($name));
    }

    /**
     * @param array<Arg|Node\VariadicPlaceholder> $args
     */
    private function stringFromArg(array $args, int $index): ?string
    {
        if (!isset($args[$index]) || !$args[$index] instanceof Arg) {
            return null;
        }

        $value = $args[$index]->value;

        return $value instanceof String_ ? $value->value : null;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    private function hasOrmMappingAttribute(array $attrGroups): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $attr->name->toString();
                if (str_starts_with($name, 'ORM\\') || str_starts_with($name, 'Doctrine\\ORM\\Mapping\\')) {
                    return true;
                }
            }
        }

        return false;
    }
}
