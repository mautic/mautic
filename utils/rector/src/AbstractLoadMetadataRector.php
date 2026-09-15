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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use Rector\Rector\AbstractRector;

/**
 * Shared machinery for the loadMetadata-to-attribute rule family. Each concrete rule converts one
 * concern (table, repository, indexes, fields, callbacks, class markers) of a static loadMetadata()
 * ClassMetadataBuilder mapping into Doctrine attributes, trims the builder calls it consumed and
 * leaves everything else behind for a sibling rule. A class is left untouched when nothing converts.
 */
abstract class AbstractLoadMetadataRector extends AbstractRector
{
    /**
     * Mautic's ClassMetadataBuilder caps every string column at this length (UTF8MB4 index limit).
     */
    protected const int DEFAULT_STRING_LENGTH = 191;

    protected bool $isHybrid = false;

    protected bool $hybridHasTable = false;

    protected bool $hybridEntityHasRepositoryClass = false;

    /**
     * @var string[]
     */
    protected const array CLASS_LEVEL_METHODS = ['setTable', 'setCustomRepositoryClass', 'addIndex', 'addUniqueConstraint'];

    /**
     * @var string[]
     */
    protected const array FIELD_CREATOR_METHODS = [
        'createField', 'addField',
        'createManyToOne', 'createOneToMany', 'createOneToOne', 'createManyToMany',
    ];

    /**
     * Canonical class-attribute order, so the six rules produce one tidy block regardless of the
     * order they run in. Lower rank sits closer to the class.
     */
    private const array CLASS_ATTRIBUTE_RANK = [
        'Entity'               => 0,
        'MappedSuperclass'     => 0,
        'Table'                => 10,
        'Index'                => 20,
        'UniqueConstraint'     => 20,
        'HasLifecycleCallbacks' => 30,
        'ChangeTrackingPolicy' => 40,
    ];

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    protected function getLoadMetadata(Class_ $node): ?ClassMethod
    {
        $loadMetadata = $node->getMethod('loadMetadata');
        if (!$loadMetadata instanceof ClassMethod || null === $loadMetadata->stmts) {
            return null;
        }

        return $loadMetadata;
    }

    /**
     * A class may already carry ORM attributes on its properties while class-level and lifecycle
     * mapping stays in loadMetadata. In that hybrid state we convert the leftover calls and merge
     * the generated attributes into the existing ones instead of duplicating.
     */
    protected function initHybridState(Class_ $node): void
    {
        $this->isHybrid                       = $this->hasOrmMappingAttribute($node->attrGroups);
        $this->hybridHasTable                 = $this->hasAttributeNamed($node->attrGroups, 'Table');
        $this->hybridEntityHasRepositoryClass = $this->entityHasRepositoryClass($node->attrGroups);
    }

    /**
     * Adds the mandatory mapping root (#[ORM\Entity] or #[ORM\MappedSuperclass]) and the
     * #[ORM\ChangeTrackingPolicy] every entity carries, when they are not present yet. Called by
     * each rule right after it converts something, so the scaffolding appears exactly when a
     * migration happens - never on a class nothing converted.
     */
    protected function ensureEntityScaffolding(Class_ $node, ClassMethod $loadMetadata): void
    {
        if (null === $this->findAttribute($node->attrGroups, ['Entity', 'MappedSuperclass'])) {
            $rootShortName = $this->hasBuilderCall($loadMetadata, 'setMappedSuperClass') ? 'MappedSuperclass' : 'Entity';
            $this->insertClassAttribute($node, $this->attribute($rootShortName, []));
        }

        if (!$this->hasAttributeNamed($node->attrGroups, 'ChangeTrackingPolicy')) {
            $this->insertClassAttribute($node, $this->attribute('ChangeTrackingPolicy', [new Arg(new String_('DEFERRED_EXPLICIT'))]));
        }
    }

    /**
     * Inserts a generated ORM class attribute at its canonical rank, keeping same-rank attributes
     * in insertion order so the resulting block reads Entity, Table, Index, UniqueConstraint,
     * HasLifecycleCallbacks, ChangeTrackingPolicy.
     */
    protected function insertClassAttribute(Class_ $node, AttributeGroup $attributeGroup): void
    {
        $rank = $this->classAttributeRank($this->attributeShortName($attributeGroup->attrs[0]));

        foreach ($node->attrGroups as $index => $existing) {
            if ($this->classAttributeRank($this->attributeShortName($existing->attrs[0])) > $rank) {
                array_splice($node->attrGroups, $index, 0, [$attributeGroup]);

                return;
            }
        }

        $node->attrGroups[] = $attributeGroup;
    }

    private function classAttributeRank(string $shortName): int
    {
        return self::CLASS_ATTRIBUTE_RANK[$shortName] ?? PHP_INT_MAX;
    }

    /**
     * Removes the given builder calls (matched by node identity) from loadMetadata and re-threads
     * every surviving call of each chain back onto the builder variable; a chain left empty drops
     * its statement.
     *
     * @param list<MethodCall> $ownedCalls
     */
    protected function removeOwnedCalls(ClassMethod $loadMetadata, array $ownedCalls): void
    {
        $ownedIds = [];
        foreach ($ownedCalls as $call) {
            $ownedIds[spl_object_id($call)] = true;
        }

        $newStmts = [];
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                $newStmts[] = $stmt;

                continue;
            }

            $calls = $this->flattenChain($stmt->expr);
            $root  = $this->chainRoot($stmt->expr);
            if (null === $calls || !$root instanceof Variable) {
                $newStmts[] = $stmt;

                continue;
            }

            $survivors = array_values(array_filter(
                $calls,
                static fn (MethodCall $call): bool => !isset($ownedIds[spl_object_id($call)])
            ));

            if ([] === $survivors) {
                continue;
            }

            if (count($survivors) === count($calls)) {
                $newStmts[] = $stmt;

                continue;
            }

            $stmt->expr = $this->rebuildChain($root, $survivors);
            $newStmts[] = $stmt;
        }

        $loadMetadata->stmts = $newStmts;
    }

    /**
     * Re-links the surviving calls into a fresh $builder->a()->b() chain rooted at the builder.
     *
     * @param list<MethodCall> $survivors
     */
    private function rebuildChain(Variable $root, array $survivors): MethodCall
    {
        $survivors[0]->var = $root;
        for ($i = 1, $count = count($survivors); $i < $count; ++$i) {
            $survivors[$i]->var = $survivors[$i - 1];
        }

        return $survivors[count($survivors) - 1];
    }

    /**
     * Drops loadMetadata once nothing is left to convert: only the builder assignment (or nothing)
     * remains. A leftover builder chain or a static helper call this family does not handle keeps
     * the method alive for a sibling or follow-up rule.
     */
    protected function removeLoadMetadataIfEmpty(Class_ $node, ClassMethod $loadMetadata): void
    {
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if ($stmt instanceof Expression && ($stmt->expr instanceof MethodCall || $stmt->expr instanceof StaticCall)) {
                return;
            }
        }

        $node->stmts = array_values(array_filter(
            $node->stmts,
            static fn (Node $stmt): bool => $stmt !== $loadMetadata
        ));
    }

    /**
     * Properties whose declaration lives in a trait or a parent get emitted into the class body so
     * their per-entity mapping can be attached; place them after trait uses.
     *
     * @param list<Property> $newProperties
     */
    protected function insertNewProperties(Class_ $node, array $newProperties): void
    {
        if ([] === $newProperties) {
            return;
        }

        // Append after any trait uses and existing properties so redeclared properties form one
        // block; with several rules each adding some, they land in rule order after the last one.
        $insertAt = 0;
        foreach ($node->stmts as $index => $stmt) {
            if ($stmt instanceof TraitUse || $stmt instanceof Property) {
                $insertAt = $index + 1;
            }
        }

        array_splice($node->stmts, $insertAt, 0, $newProperties);
    }

    protected function hasBuilderCall(ClassMethod $loadMetadata, string $methodName): bool
    {
        foreach ((array) $loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            $calls = $this->flattenChain($stmt->expr);
            foreach ($calls ?? [] as $call) {
                if ($methodName === $this->methodName($call)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Splits a flattened builder chain into segments, each headed by a class-level or field-level
     * creator; trailing modifier calls (columnName, build, addJoinColumn, ...) attach to the
     * segment they follow. Null when a modifier precedes any creator.
     *
     * @param list<MethodCall> $calls
     *
     * @return list<list<MethodCall>>|null
     */
    protected function splitIntoSegments(array $calls): ?array
    {
        $starters = [...self::CLASS_LEVEL_METHODS, 'setMappedSuperClass', ...self::FIELD_CREATOR_METHODS];

        $segments = [];
        $current  = null;

        foreach ($calls as $call) {
            if (in_array($this->methodName($call), $starters, true)) {
                if (null !== $current) {
                    $segments[] = $current;
                }

                $current = [$call];

                continue;
            }

            if (null === $current) {
                return null;
            }

            $current[] = $call;
        }

        if (null !== $current) {
            $segments[] = $current;
        }

        return $segments;
    }

    /**
     * Flattens $builder->a()->b()->c() into [a, b, c]; null when the chain is not rooted at a
     * variable. The builder may be held under any name, so we do not require it to be $builder.
     *
     * @return list<MethodCall>|null
     */
    protected function flattenChain(MethodCall $call): ?array
    {
        $calls   = [];
        $current = $call;

        while ($current instanceof MethodCall) {
            $calls[] = $current;
            $current = $current->var;
        }

        if (!$current instanceof Variable) {
            return null;
        }

        return array_reverse($calls);
    }

    protected function chainRoot(MethodCall $call): ?Variable
    {
        $current = $call;
        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        return $current instanceof Variable ? $current : null;
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    protected function handleFieldChain(array $calls): ?array
    {
        return match ($this->methodName($calls[0])) {
            'createField'      => $this->handleCreateField($calls),
            'addField'         => $this->handleAddField($calls),
            'createManyToOne'  => $this->handleAssociation($calls, 'ManyToOne'),
            'createOneToMany'  => $this->handleAssociation($calls, 'OneToMany'),
            'createOneToOne'   => $this->handleAssociation($calls, 'OneToOne'),
            'createManyToMany' => $this->handleManyToMany($calls),
            default            => null,
        };
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleCreateField(array $calls): ?array
    {
        $createField = $calls[0];
        $fieldName   = $this->stringArg($createField, 0);
        $typeExpr    = $this->typeExpr($createField, 1);
        if (null === $fieldName || null === $typeExpr) {
            return null;
        }

        $columnName  = null;
        $length      = null;
        $nullable    = false;
        $unique      = false;
        $isPrimary   = false;
        $generated   = false;
        $genStrategy = null;
        $options     = [];
        $sawBuild    = false;

        foreach (array_slice($calls, 1) as $call) {
            switch ($this->methodName($call)) {
                case 'columnName':
                    $columnName = $this->stringArg($call, 0);
                    if (null === $columnName) {
                        return null;
                    }
                    break;

                case 'length':
                    $length = $this->intArg($call, 0);
                    if (null === $length) {
                        return null;
                    }
                    break;

                case 'nullable':
                    $nullable = $this->boolArg($call, 0, true);
                    break;

                case 'unique':
                    $unique = $this->boolArg($call, 0, true);
                    break;

                case 'makePrimaryKey':
                    $isPrimary = true;
                    break;

                case 'generatedValue':
                    $generated   = true;
                    $genStrategy = $this->stringArg($call, 0);
                    break;

                case 'option':
                    $item = $this->optionItem($call);
                    if (null === $item) {
                        return null;
                    }
                    $options[] = $item;
                    break;

                case 'build':
                    $sawBuild = true;
                    break;

                default:
                    return null;
            }
        }

        if (!$sawBuild) {
            return null;
        }

        return [$fieldName => $this->columnAttributes(
            $fieldName,
            $columnName,
            $typeExpr,
            $length,
            $nullable,
            $unique,
            $options,
            $isPrimary,
            $generated,
            $genStrategy,
        )];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddField(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call      = $calls[0];
        $fieldName = $this->stringArg($call, 0);
        $typeExpr  = $this->typeExpr($call, 1);
        if (null === $fieldName || null === $typeExpr) {
            return null;
        }

        $columnName = null;
        $length     = null;
        $nullable   = false;
        $unique     = false;

        if (isset($call->args[2])) {
            if (!$call->args[2] instanceof Arg || !$call->args[2]->value instanceof Array_) {
                return null;
            }

            foreach ($call->args[2]->value->items as $item) {
                if (!$item instanceof ArrayItem || !$item->key instanceof String_) {
                    return null;
                }

                switch ($item->key->value) {
                    case 'columnName':
                        if (!$item->value instanceof String_) {
                            return null;
                        }
                        $columnName = $item->value->value;
                        break;

                    case 'length':
                        if (!$item->value instanceof Int_) {
                            return null;
                        }
                        $length = $item->value->value;
                        break;

                    case 'nullable':
                        $nullable = $item->value instanceof ConstFetch && $this->isName($item->value, 'true');
                        break;

                    case 'unique':
                        $unique = $item->value instanceof ConstFetch && $this->isName($item->value, 'true');
                        break;

                    default:
                        return null;
                }
            }
        }

        return [$fieldName => $this->columnAttributes($fieldName, $columnName, $typeExpr, $length, $nullable, $unique, [], false, false, null)];
    }

    /**
     * createManyToOne('field', Target::class)->...->build()
     * createOneToMany('field', Target::class)->mappedBy('x')->...->build().
     *
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAssociation(array $calls, string $kind): ?array
    {
        $create    = $calls[0];
        $fieldName = $this->stringArg($create, 0);
        if (null === $fieldName || !isset($create->args[1]) || !$create->args[1] instanceof Arg) {
            return null;
        }

        $target = $this->resolveTargetEntity($create->args[1]->value);

        $mappedBy      = null;
        $inversedBy    = null;
        $cascade       = [];
        $fetch         = null;
        $orphanRemoval = false;
        $isPrimary     = false;
        $indexBy       = null;
        $orderBy       = null;
        $joinColumns   = [];
        $sawBuild      = false;

        foreach (array_slice($calls, 1) as $call) {
            $name = $this->methodName($call);

            $cascadeType = $this->cascadeType($name);
            if (null !== $cascadeType) {
                $cascade[] = $cascadeType;
                continue;
            }

            $fetchMode = $this->fetchMode($name);
            if (null !== $fetchMode) {
                $fetch = $fetchMode;
                continue;
            }

            switch ($name) {
                case 'mappedBy':
                    $mappedBy = $this->stringArg($call, 0);
                    if (null === $mappedBy) {
                        return null;
                    }
                    break;

                case 'inversedBy':
                    $inversedBy = $this->stringArg($call, 0);
                    if (null === $inversedBy) {
                        return null;
                    }
                    break;

                case 'setIndexBy':
                    $indexBy = $this->stringArg($call, 0);
                    if (null === $indexBy) {
                        return null;
                    }
                    break;

                case 'setOrderBy':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg || !$call->args[0]->value instanceof Array_) {
                        return null;
                    }
                    $orderBy = $call->args[0]->value;
                    break;

                case 'orphanRemoval':
                    $orphanRemoval = $this->boolArg($call, 0, true);
                    break;

                case 'makePrimaryKey':
                case 'isPrimaryKey':
                    $isPrimary = true;
                    break;

                case 'addJoinColumn':
                    $joinColumn = $this->parseJoinColumn($call);
                    if (null === $joinColumn) {
                        return null;
                    }
                    $joinColumns[] = $joinColumn;
                    break;

                case 'build':
                    $sawBuild = true;
                    break;

                default:
                    // isOwnershipParent, setJoinTable, addInverseJoinColumn, ... are not handled.
                    return null;
            }
        }

        if (!$sawBuild) {
            return null;
        }

        return [$fieldName => $this->associationAttributes(
            $kind,
            $target,
            $mappedBy,
            $inversedBy,
            $cascade,
            $fetch,
            $orphanRemoval,
            $isPrimary,
            $indexBy,
            $orderBy,
            $joinColumns,
        )];
    }

    /**
     * createManyToMany('field', Target::class)->setJoinTable('xref')
     *     ->addJoinColumn(...)->addInverseJoinColumn(...)->build().
     *
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleManyToMany(array $calls): ?array
    {
        $create    = $calls[0];
        $fieldName = $this->stringArg($create, 0);
        if (null === $fieldName || !isset($create->args[1]) || !$create->args[1] instanceof Arg) {
            return null;
        }

        $target = $this->resolveTargetEntity($create->args[1]->value);

        $mappedBy           = null;
        $inversedBy         = null;
        $cascade            = [];
        $fetch              = null;
        $orphanRemoval      = false;
        $indexBy            = null;
        $orderBy            = null;
        $joinTable          = null;
        $joinColumns        = [];
        $inverseJoinColumns = [];
        $sawBuild           = false;

        foreach (array_slice($calls, 1) as $call) {
            $name = $this->methodName($call);

            $cascadeType = $this->cascadeType($name);
            if (null !== $cascadeType) {
                $cascade[] = $cascadeType;
                continue;
            }

            $fetchMode = $this->fetchMode($name);
            if (null !== $fetchMode) {
                $fetch = $fetchMode;
                continue;
            }

            switch ($name) {
                case 'mappedBy':
                    $mappedBy = $this->stringArg($call, 0);
                    if (null === $mappedBy) {
                        return null;
                    }
                    break;

                case 'inversedBy':
                    $inversedBy = $this->stringArg($call, 0);
                    if (null === $inversedBy) {
                        return null;
                    }
                    break;

                case 'setIndexBy':
                    $indexBy = $this->stringArg($call, 0);
                    if (null === $indexBy) {
                        return null;
                    }
                    break;

                case 'setOrderBy':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg || !$call->args[0]->value instanceof Array_) {
                        return null;
                    }
                    $orderBy = $call->args[0]->value;
                    break;

                case 'orphanRemoval':
                    $orphanRemoval = $this->boolArg($call, 0, true);
                    break;

                case 'setJoinTable':
                    $joinTable = $this->stringArg($call, 0);
                    if (null === $joinTable) {
                        return null;
                    }
                    break;

                case 'addJoinColumn':
                    $joinColumn = $this->parseJoinColumn($call);
                    if (null === $joinColumn) {
                        return null;
                    }
                    $joinColumns[] = $joinColumn;
                    break;

                case 'addInverseJoinColumn':
                    $inverseJoinColumn = $this->parseJoinColumn($call);
                    if (null === $inverseJoinColumn) {
                        return null;
                    }
                    $inverseJoinColumns[] = $inverseJoinColumn;
                    break;

                case 'build':
                    $sawBuild = true;
                    break;

                default:
                    return null;
            }
        }

        if (!$sawBuild) {
            return null;
        }

        return [$fieldName => $this->manyToManyAttributes(
            $target,
            $mappedBy,
            $inversedBy,
            $cascade,
            $fetch,
            $orphanRemoval,
            $indexBy,
            $orderBy,
            $joinTable,
            $joinColumns,
            $inverseJoinColumns,
        )];
    }

    /**
     * @param list<string>                                                                            $cascade
     * @param list<array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}> $joinColumns
     * @param list<array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}> $inverseJoinColumns
     *
     * @return list<AttributeGroup>
     */
    private function manyToManyAttributes(
        Expr $target,
        ?string $mappedBy,
        ?string $inversedBy,
        array $cascade,
        ?string $fetch,
        bool $orphanRemoval,
        ?string $indexBy,
        ?Array_ $orderBy,
        ?string $joinTable,
        array $joinColumns,
        array $inverseJoinColumns,
    ): array {
        $args = [];

        if (null !== $mappedBy) {
            $args[] = $this->namedArg('mappedBy', new String_($mappedBy));
        }

        $args[] = $this->namedArg('targetEntity', $target);

        if (null !== $inversedBy) {
            $args[] = $this->namedArg('inversedBy', new String_($inversedBy));
        }

        if ([] !== $cascade) {
            $args[] = $this->namedArg('cascade', new Array_(array_map(
                static fn (string $c): ArrayItem => new ArrayItem(new String_($c)),
                $cascade,
            )));
        }

        if (null !== $fetch) {
            $args[] = $this->namedArg('fetch', new String_($fetch));
        }

        if ($orphanRemoval) {
            $args[] = $this->namedArg('orphanRemoval', new ConstFetch(new Name('true')));
        }

        if (null !== $indexBy) {
            $args[] = $this->namedArg('indexBy', new String_($indexBy));
        }

        $attributes = [$this->attribute('ManyToMany', $args)];

        if (null !== $joinTable) {
            $attributes[] = $this->attribute('JoinTable', [$this->namedArg('name', new String_($joinTable))]);
        }

        foreach ($joinColumns as $joinColumn) {
            $attributes[] = $this->joinColumnAttribute($joinColumn);
        }

        foreach ($inverseJoinColumns as $inverseJoinColumn) {
            $attributes[] = $this->joinColumnAttribute($inverseJoinColumn, 'InverseJoinColumn');
        }

        if (null !== $orderBy) {
            $attributes[] = $this->attribute('OrderBy', [new Arg($orderBy)]);
        }

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
     * addJoinColumn($name, $ref = 'id', $nullable = true, $unique = false, $onDelete = null).
     *
     * @return array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}|null
     */
    private function parseJoinColumn(MethodCall $call): ?array
    {
        $name = $this->stringArg($call, 0);
        if (null === $name) {
            return null;
        }

        $ref      = $this->stringArg($call, 1) ?? 'id';
        $nullable = $this->boolArg($call, 2, true);
        $unique   = $this->boolArg($call, 3, false);
        $onDelete = $this->stringArg($call, 4);

        // Reject unparseable positional args (e.g. a variable) rather than guessing.
        if (isset($call->args[1]) && null === $this->stringArg($call, 1)) {
            return null;
        }
        if (isset($call->args[4]) && null === $onDelete) {
            return null;
        }

        return $this->joinColumn($name, $ref, $nullable, $unique, $onDelete);
    }

    /**
     * @param list<string>                                                                            $cascade
     * @param list<array{name: string, ref: string, nullable: bool, unique: bool, onDelete: ?string}> $joinColumns
     *
     * @return list<AttributeGroup>
     */
    private function associationAttributes(
        string $kind,
        Expr $target,
        ?string $mappedBy,
        ?string $inversedBy,
        array $cascade,
        ?string $fetch,
        bool $orphanRemoval,
        bool $isPrimary,
        ?string $indexBy,
        ?Array_ $orderBy,
        array $joinColumns,
    ): array {
        $args = [];

        if (null !== $mappedBy) {
            $args[] = $this->namedArg('mappedBy', new String_($mappedBy));
        }

        $args[] = $this->namedArg('targetEntity', $target);

        if (null !== $inversedBy) {
            $args[] = $this->namedArg('inversedBy', new String_($inversedBy));
        }

        if ([] !== $cascade) {
            $args[] = $this->namedArg('cascade', new Array_(array_map(
                static fn (string $c): ArrayItem => new ArrayItem(new String_($c)),
                $cascade,
            )));
        }

        if (null !== $fetch) {
            $args[] = $this->namedArg('fetch', new String_($fetch));
        }

        if ($orphanRemoval) {
            $args[] = $this->namedArg('orphanRemoval', new ConstFetch(new Name('true')));
        }

        if (null !== $indexBy) {
            $args[] = $this->namedArg('indexBy', new String_($indexBy));
        }

        $attributes = [];

        if ($isPrimary) {
            $attributes[] = $this->attribute('Id', []);
        }

        $attributes[] = $this->attribute($kind, $args);

        foreach ($joinColumns as $joinColumn) {
            $attributes[] = $this->joinColumnAttribute($joinColumn);
        }

        if (null !== $orderBy) {
            $attributes[] = $this->attribute('OrderBy', [new Arg($orderBy)]);
        }

        return $attributes;
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

    private function cascadeType(string $method): ?string
    {
        return match ($method) {
            'cascadeAll'     => 'all',
            'cascadePersist' => 'persist',
            'cascadeRemove'  => 'remove',
            'cascadeMerge'   => 'merge',
            'cascadeDetach'  => 'detach',
            'cascadeRefresh' => 'refresh',
            default          => null,
        };
    }

    private function fetchMode(string $method): ?string
    {
        return match ($method) {
            'fetchEager'     => 'EAGER',
            'fetchExtraLazy' => 'EXTRA_LAZY',
            'fetchLazy'      => 'LAZY',
            default          => null,
        };
    }

    /**
     * @param ArrayItem[] $options
     *
     * @return list<AttributeGroup>
     */
    private function columnAttributes(
        string $fieldName,
        ?string $columnName,
        Expr $typeExpr,
        ?int $length,
        bool $nullable,
        bool $unique,
        array $options,
        bool $isPrimary,
        bool $generated,
        ?string $generatedStrategy,
    ): array {
        $args = [];

        if (null !== $columnName && $columnName !== $fieldName) {
            $args[] = $this->namedArg('name', new String_($columnName));
        }

        $args[] = $this->namedArg('type', $typeExpr);

        if (null === $length && $this->isStringType($typeExpr)) {
            $length = self::DEFAULT_STRING_LENGTH;
        }

        if (null !== $length) {
            $args[] = $this->namedArg('length', new Int_($length));
        }

        if ($nullable) {
            $args[] = $this->namedArg('nullable', new ConstFetch(new Name('true')));
        }

        if ($unique) {
            $args[] = $this->namedArg('unique', new ConstFetch(new Name('true')));
        }

        if ([] !== $options) {
            $args[] = $this->namedArg('options', new Array_($options));
        }

        $attributes = [];
        if ($isPrimary) {
            $attributes[] = $this->attribute('Id', []);
        }

        $attributes[] = $this->attribute('Column', $args);

        if ($generated) {
            $generatedArgs = (null !== $generatedStrategy && 'AUTO' !== $generatedStrategy)
                ? [$this->namedArg('strategy', new String_($generatedStrategy))]
                : [];
            $attributes[] = $this->attribute('GeneratedValue', $generatedArgs);
        }

        return $attributes;
    }

    /**
     * @param Arg[] $args
     */
    protected function attribute(string $shortName, array $args): AttributeGroup
    {
        return new AttributeGroup([new Attribute(new Name('ORM\\'.$shortName), array_values($args))]);
    }

    protected function namedArg(string $name, Expr $value): Arg
    {
        return new Arg($value, false, false, [], new Identifier($name));
    }

    /**
     * A createManyToOne('field', 'Target') string target becomes Target::class. Doctrine resolves a
     * relative name against the entity's namespace, so a short name stays a same-namespace ::class
     * and a fully-qualified string becomes a \Fully\Qualified::class. Non-string targets pass through.
     */
    private function resolveTargetEntity(Expr $value): Expr
    {
        if (!$value instanceof String_) {
            return $value;
        }

        $className = ltrim($value->value, '\\');
        $name      = str_contains($className, '\\') ? new FullyQualified($className) : new Name($className);

        return new ClassConstFetch($name, new Identifier('class'));
    }

    /**
     * option($key, $value) -> an ['key' => value] array item, or null when unparseable.
     */
    private function optionItem(MethodCall $call): ?ArrayItem
    {
        if (!isset($call->args[0], $call->args[1]) || !$call->args[0] instanceof Arg || !$call->args[1] instanceof Arg) {
            return null;
        }

        $key = $call->args[0]->value;
        if (!$key instanceof String_) {
            return null;
        }

        return new ArrayItem($call->args[1]->value, $key);
    }

    /**
     * A column type argument: a string literal or a Types::* constant, passed through verbatim.
     */
    private function typeExpr(MethodCall $call, int $index): ?Expr
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;

        if ($value instanceof String_) {
            return $value;
        }

        if ($value instanceof ClassConstFetch && $value->class instanceof Name && 'Types' === $value->class->getLast()) {
            return $value;
        }

        return null;
    }

    private function isStringType(Expr $typeExpr): bool
    {
        if ($typeExpr instanceof String_) {
            return 'string' === $typeExpr->value;
        }

        return $typeExpr instanceof ClassConstFetch
            && $typeExpr->name instanceof Identifier
            && 'STRING' === $typeExpr->name->toString();
    }

    protected function lifecycleEventShortName(string $event): ?string
    {
        return match ($event) {
            'prePersist'  => 'PrePersist',
            'postPersist' => 'PostPersist',
            'preUpdate'   => 'PreUpdate',
            'postUpdate'  => 'PostUpdate',
            'preRemove'   => 'PreRemove',
            'postRemove'  => 'PostRemove',
            'postLoad'    => 'PostLoad',
            'preFlush'    => 'PreFlush',
            default       => null,
        };
    }

    /**
     * The event name argument of addLifecycleEvent(): a string literal or a Doctrine Events::*
     * constant, whose constant name equals the event string (Events::preUpdate === 'preUpdate').
     */
    protected function lifecycleEventArg(MethodCall $call, int $index): ?string
    {
        $string = $this->stringArg($call, $index);
        if (null !== $string) {
            return $string;
        }

        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;
        if ($value instanceof ClassConstFetch
            && $value->class instanceof Name && 'Events' === $value->class->getLast()
            && $value->name instanceof Identifier
        ) {
            return $value->name->toString();
        }

        return null;
    }

    protected function methodName(MethodCall $call): string
    {
        return $call->name instanceof Identifier ? $call->name->toString() : '';
    }

    /**
     * The raw expression of a positional argument, passed through verbatim; null when absent.
     */
    protected function argValue(MethodCall $call, int $index): ?Expr
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        return $call->args[$index]->value;
    }

    protected function stringArg(MethodCall $call, int $index): ?string
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;

        return $value instanceof String_ ? $value->value : null;
    }

    protected function intArg(MethodCall $call, int $index): ?int
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;

        return $value instanceof Int_ ? $value->value : null;
    }

    protected function boolArg(MethodCall $call, int $index, bool $default): bool
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return $default;
        }

        $value = $call->args[$index]->value;

        return $value instanceof ConstFetch && $this->isName($value, 'true');
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    protected function hasOrmMappingAttribute(array $attrGroups): bool
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

    /**
     * The short name of an attribute (Entity, Table, ...), stripped of the ORM\ or FQCN prefix.
     */
    protected function attributeShortName(Attribute $attr): string
    {
        return $attr->name->getLast();
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    protected function hasAttributeNamed(array $attrGroups, string $shortName): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($shortName === $this->attributeShortName($attr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     * @param string[]         $shortNames
     */
    protected function findAttribute(array $attrGroups, array $shortNames): ?Attribute
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (in_array($this->attributeShortName($attr), $shortNames, true)) {
                    return $attr;
                }
            }
        }

        return null;
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    protected function entityHasRepositoryClass(array $attrGroups): bool
    {
        $entity = $this->findAttribute($attrGroups, ['Entity']);
        if (!$entity instanceof Attribute) {
            return false;
        }

        foreach ($entity->args as $arg) {
            if ($arg instanceof Arg && $arg->name instanceof Identifier && 'repositoryClass' === $arg->name->toString()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The declared type of a property inherited from a parent class, mirrored so a redeclaration
     * stays compatible with the parent (PHP requires an identical type). Null when the parent, the
     * property or its type cannot be resolved, leaving the redeclaration untyped.
     */
    protected function parentPropertyType(Class_ $class, string $propertyName): Identifier|Name|NullableType|null
    {
        if (!$class->extends instanceof Name) {
            return null;
        }

        // Reflect the entity itself so an inherited property's declared type resolves through the
        // whole parent chain without having to resolve the parent's alias by hand.
        $className = $this->getName($class);
        if (null === $className || !class_exists($className)) {
            return null;
        }

        $reflection = new \ReflectionClass($className);
        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $type = $reflection->getProperty($propertyName)->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        $typeName = $type->getName();
        $typeNode = $type->isBuiltin() ? new Identifier($typeName) : new FullyQualified($typeName);

        if ($type->allowsNull() && 'null' !== $typeName && 'mixed' !== $typeName) {
            return new NullableType($typeNode);
        }

        return $typeNode;
    }

    protected function findProperty(Class_ $class, string $name): Property|Param|null
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                if ($this->isName($prop, $name)) {
                    return $property;
                }
            }
        }

        // Constructor-promoted properties are Param nodes, not Stmt\Property.
        $constructor = $class->getMethod('__construct');
        if ($constructor instanceof ClassMethod) {
            foreach ($constructor->params as $param) {
                if (0 !== $param->flags && $param->var instanceof Variable && $this->isName($param->var, $name)) {
                    return $param;
                }
            }
        }

        return null;
    }

    /**
     * Redeclares a mapped property that lives in a parent (often a vendor base class we cannot
     * annotate) as a protected property in this class, carrying the mapping attributes.
     *
     * @param list<AttributeGroup> $attributeGroups
     */
    protected function redeclaredProperty(Class_ $node, string $propertyName, array $attributeGroups): Property
    {
        return new Property(
            Modifiers::PROTECTED,
            [new PropertyItem($propertyName)],
            [],
            $this->parentPropertyType($node, $propertyName),
            $attributeGroups,
        );
    }

    /**
     * Shared body for the field/association rules: converts every segment headed by one of
     * $ownedCreators into property attributes, trims those segments and leaves the rest.
     *
     * @param list<string> $ownedCreators
     */
    protected function refactorFieldCreators(Class_ $node, ClassMethod $loadMetadata, array $ownedCreators): ?Class_
    {
        $owned            = [];
        $propertyResolved = [];
        $newProperties    = [];

        foreach ($loadMetadata->stmts as $stmt) {
            if (!$stmt instanceof Expression || !$stmt->expr instanceof MethodCall) {
                continue;
            }

            $calls    = $this->flattenChain($stmt->expr);
            $segments = null === $calls ? null : $this->splitIntoSegments($calls);
            if (null === $segments) {
                continue;
            }

            foreach ($segments as $segment) {
                if (!in_array($this->methodName($segment[0]), $ownedCreators, true)) {
                    continue;
                }

                $fields = $this->handleFieldChain($segment);
                if (null === $fields) {
                    continue;
                }

                $resolved = $this->resolveFields($node, $fields);
                if (null === $resolved) {
                    continue;
                }

                [$resolvedProperties, $resolvedNewProperties] = $resolved;

                $propertyResolved = array_merge($propertyResolved, $resolvedProperties);
                $newProperties    = array_merge($newProperties, $resolvedNewProperties);
                $owned            = array_merge($owned, $segment);
            }
        }

        if ([] === $owned && [] === $newProperties) {
            return null;
        }

        foreach ($propertyResolved as [$property, $attributeGroups]) {
            $property->attrGroups = array_merge($property->attrGroups, $attributeGroups);
        }

        $this->removeOwnedCalls($loadMetadata, $owned);
        $this->ensureEntityScaffolding($node, $loadMetadata);
        $this->removeLoadMetadataIfEmpty($node, $loadMetadata);
        $this->insertNewProperties($node, $newProperties);

        return $node;
    }

    /**
     * Resolves a segment's fields against the class body. Returns null - leaving the segment in
     * loadMetadata - when the property is already attribute-mapped (a partially converted class), or
     * when a property is missing and there is no parent to redeclare it from.
     *
     * @param array<string, list<AttributeGroup>> $fields
     *
     * @return array{0: list<array{0: Property|Param, 1: list<AttributeGroup>}>, 1: list<Property>}|null
     */
    private function resolveFields(Class_ $node, array $fields): ?array
    {
        $propertyResolved = [];
        $newProperties    = [];

        foreach ($fields as $fieldName => $attributeGroups) {
            $property = $this->findProperty($node, $fieldName);

            // The property already carries ORM mapping (its own #[ORM\Column]/relation attribute):
            // leave the builder call be, so a class mid-conversion is not double-mapped.
            if (null !== $property && $this->hasOrmMappingAttribute($property->attrGroups)) {
                return null;
            }

            if ($property instanceof Property || $property instanceof Param) {
                $propertyResolved[] = [$property, $attributeGroups];

                continue;
            }

            // The property is not declared in this class. When it extends a parent, the mapped
            // property lives there - often a vendor base class we cannot annotate - so redeclare it.
            if (null === $node->extends) {
                return null;
            }

            $newProperties[] = $this->redeclaredProperty($node, $fieldName, $attributeGroups);
        }

        return [$propertyResolved, $newProperties];
    }
}
