<?php

declare(strict_types=1);

namespace Utils\Rector;

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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;

/**
 * Converts a static loadMetadata() ClassMetadataBuilder mapping into Doctrine attributes.
 *
 * Deliberately conservative: if the method contains any builder call this rule does not
 * understand, the whole class is left untouched. A partial rewrite would silently drift
 * the schema, so it is all-or-nothing per class.
 *
 * Not yet handled (these make a class bail): isOwnershipParent and custom static
 * helpers such as addProjectsField/addTranslationMetadata.
 */
final class LoadMetadataToDoctrineAttributeRector extends AbstractRector
{
    /**
     * Mautic's ClassMetadataBuilder caps every string column at this length (UTF8MB4 index limit).
     */
    private const int DEFAULT_STRING_LENGTH = 191;

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

        $result = $this->interpret($loadMetadata->stmts);
        if (null === $result) {
            return null;
        }

        [$classAttributes, $propertyAttributes] = $result;

        // Resolve every target property up front. Bail before mutating anything if one
        // is missing, otherwise a half-applied change makes Rector loop forever.
        $resolved = [];
        foreach ($propertyAttributes as $propertyName => $attributeGroups) {
            $property = $this->findProperty($node, $propertyName);
            if (!$property instanceof Property) {
                return null;
            }

            $resolved[] = [$property, $attributeGroups];
        }

        // Every entity gets DEFERRED_EXPLICIT from the builder constructor.
        $classAttributes[] = $this->attribute('ChangeTrackingPolicy', [new Arg(new String_('DEFERRED_EXPLICIT'))]);

        foreach ($resolved as [$property, $attributeGroups]) {
            $property->attrGroups = array_merge($property->attrGroups, $attributeGroups);
        }

        $node->attrGroups = array_merge($node->attrGroups, $classAttributes);

        $node->stmts = array_values(array_filter(
            $node->stmts,
            static fn (Node $stmt): bool => $stmt !== $loadMetadata
        ));

        return $node;
    }

    /**
     * @param Node\Stmt[] $stmts
     *
     * @return array{list<AttributeGroup>, array<string, list<AttributeGroup>>}|null
     */
    private function interpret(array $stmts): ?array
    {
        $classAttributes    = [];
        $propertyAttributes = [];
        $entityArgs         = [];

        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Expression) {
                return null;
            }

            $expr = $stmt->expr;

            // $builder = new ClassMetadataBuilder($metadata);
            if ($expr instanceof Expr\Assign) {
                if (!$expr->var instanceof Variable || !$this->isName($expr->var, 'builder') || !$expr->expr instanceof New_) {
                    return null;
                }

                continue;
            }

            // self::addUuidField($builder) and friends.
            if ($expr instanceof StaticCall) {
                $fields = $this->handleStaticHelper($expr);
                if (null === $fields) {
                    return null;
                }

                foreach ($fields as $fieldName => $attributeGroups) {
                    $propertyAttributes[$fieldName] = $attributeGroups;
                }

                continue;
            }

            if (!$expr instanceof MethodCall) {
                return null;
            }

            $calls = $this->flattenChain($expr);
            if (null === $calls) {
                return null;
            }

            $first = $this->methodName($calls[0]);

            if (in_array($first, ['setTable', 'setCustomRepositoryClass', 'addIndex', 'addFulltextIndex'], true)) {
                $handled = $this->handleClassChain($calls);
                if (null === $handled) {
                    return null;
                }

                $classAttributes = array_merge($classAttributes, $handled['attributes']);
                $entityArgs      = array_merge($entityArgs, $handled['entityArgs']);

                continue;
            }

            $fields = $this->handleFieldChain($calls);
            if (null === $fields) {
                return null;
            }

            foreach ($fields as $fieldName => $attributeGroups) {
                $propertyAttributes[$fieldName] = $attributeGroups;
            }
        }

        // #[ORM\Entity] is mandatory; place it first.
        array_unshift($classAttributes, $this->attribute('Entity', $entityArgs));

        return [$classAttributes, $propertyAttributes];
    }

    /**
     * Flattens $builder->a()->b()->c() into [a, b, c]; null when not rooted at $builder.
     *
     * @return list<MethodCall>|null
     */
    private function flattenChain(MethodCall $call): ?array
    {
        $calls   = [];
        $current = $call;

        while ($current instanceof MethodCall) {
            $calls[] = $current;
            $current = $current->var;
        }

        if (!$current instanceof Variable || !$this->isName($current, 'builder')) {
            return null;
        }

        return array_reverse($calls);
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array{attributes: list<AttributeGroup>, entityArgs: list<Arg>}|null
     */
    private function handleClassChain(array $calls): ?array
    {
        $attributes = [];
        $entityArgs = [];

        foreach ($calls as $call) {
            switch ($this->methodName($call)) {
                case 'setTable':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg) {
                        return null;
                    }

                    $attributes[] = $this->attribute('Table', [$this->namedArg('name', $call->args[0]->value)]);
                    break;

                case 'setCustomRepositoryClass':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg) {
                        return null;
                    }

                    $entityArgs[] = $this->namedArg('repositoryClass', $call->args[0]->value);
                    break;

                case 'addIndex':
                case 'addFulltextIndex':
                    if (2 !== count($call->args) || !$call->args[0] instanceof Arg || !$call->args[1] instanceof Arg) {
                        return null;
                    }

                    if (!$call->args[0]->value instanceof Array_) {
                        return null;
                    }

                    $indexArgs = [
                        $this->namedArg('columns', $call->args[0]->value),
                        $this->namedArg('name', $call->args[1]->value),
                    ];

                    if ('addFulltextIndex' === $this->methodName($call)) {
                        $indexArgs[] = $this->namedArg('flags', new Array_([new ArrayItem(new String_('fulltext'))]));
                    }

                    $attributes[] = $this->attribute('Index', $indexArgs);
                    break;

                default:
                    return null;
            }
        }

        return ['attributes' => $attributes, 'entityArgs' => $entityArgs];
    }

    /**
     * Static mapping helpers shared across entities, called as self::x($builder).
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleStaticHelper(StaticCall $call): ?array
    {
        if (!$call->class instanceof Name || !in_array($call->class->toString(), ['self', 'static'], true)) {
            return null;
        }

        if (!$call->name instanceof Identifier) {
            return null;
        }

        // addUuidField($builder): createField('uuid', Types::GUID)->nullable()->build()
        if ('addUuidField' === $call->name->toString()) {
            return ['uuid' => $this->columnAttributes('uuid', null, new String_('guid'), null, true, false, [], false, false, null)];
        }

        return null;
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleFieldChain(array $calls): ?array
    {
        return match ($this->methodName($calls[0])) {
            'createField'      => $this->handleCreateField($calls),
            'addBigIntIdField' => $this->handleBigIntIdField($calls),
            'addDateAdded'     => $this->handleDateAdded($calls),
            'addId'            => $this->handleAddId($calls),
            'addIdColumns'     => $this->handleAddIdColumns($calls),
            'addNullableField' => $this->handleAddNullableField($calls),
            'addNamedField'    => $this->handleAddNamedField($calls),
            'addField'         => $this->handleAddField($calls),
            'addPublishDates'  => $this->handleAddPublishDates($calls),
            'createManyToOne'  => $this->handleAssociation($calls, 'ManyToOne'),
            'createOneToMany'  => $this->handleAssociation($calls, 'OneToMany'),
            'createOneToOne'   => $this->handleAssociation($calls, 'OneToOne'),
            'createManyToMany' => $this->handleManyToMany($calls),
            'addLead'          => $this->handleContactHelper($calls, 'lead', 'lead_id', 'Mautic\\LeadBundle\\Entity\\Lead'),
            'addContact'       => $this->handleContactHelper($calls, 'contact', 'contact_id', 'Mautic\\LeadBundle\\Entity\\Lead'),
            'addCategory'      => $this->handleAddCategory($calls),
            'addIpAddress'     => $this->handleAddIpAddress($calls),
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
    private function handleBigIntIdField(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call       = $calls[0];
        $fieldName  = $this->stringArg($call, 0) ?? 'id';
        $columnName = $this->stringArg($call, 1) ?? 'id';
        $isPrimary  = $this->boolArg($call, 2, true);
        $isNullable = $this->boolArg($call, 3, false);

        $options = [new ArrayItem(new ConstFetch(new Name('true')), new String_('unsigned'))];

        return [$fieldName => $this->columnAttributes(
            $fieldName,
            $columnName,
            new String_('bigint'),
            null,
            !$isPrimary && $isNullable,
            false,
            $options,
            $isPrimary,
            $isPrimary,
            null,
        )];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddId(array $calls): ?array
    {
        if (1 !== count($calls) || [] !== $calls[0]->args) {
            return null;
        }

        $options = [new ArrayItem(new ConstFetch(new Name('true')), new String_('unsigned'))];

        return ['id' => $this->columnAttributes(
            'id',
            null,
            new String_('integer'),
            null,
            false,
            false,
            $options,
            true,
            true,
            null,
        )];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddIdColumns(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call       = $calls[0];
        $nameColumn = $this->stringOrFalseArg($call, 0, 'name');
        $descColumn = $this->stringOrFalseArg($call, 1, 'description');

        $result = ['id' => $this->columnAttributes(
            'id',
            null,
            new String_('integer'),
            null,
            false,
            false,
            [new ArrayItem(new ConstFetch(new Name('true')), new String_('unsigned'))],
            true,
            true,
            null,
        )];

        if (is_string($nameColumn)) {
            $result[$nameColumn] = $this->columnAttributes($nameColumn, null, new String_('string'), null, false, false, [], false, false, null);
        }

        if (is_string($descColumn)) {
            $result[$descColumn] = $this->columnAttributes($descColumn, null, new String_('text'), null, true, false, [], false, false, null);
        }

        return $result;
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddNullableField(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call      = $calls[0];
        $fieldName = $this->stringArg($call, 0);
        if (null === $fieldName) {
            return null;
        }

        $typeExpr   = isset($call->args[1]) ? $this->typeExpr($call, 1) : new String_('string');
        $columnName = $this->stringArg($call, 2);
        if (null === $typeExpr) {
            return null;
        }

        return [$fieldName => $this->columnAttributes($fieldName, $columnName, $typeExpr, null, true, false, [], false, false, null)];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddNamedField(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call       = $calls[0];
        $fieldName  = $this->stringArg($call, 0);
        $typeExpr   = $this->typeExpr($call, 1);
        $columnName = $this->stringArg($call, 2);
        if (null === $fieldName || null === $typeExpr || null === $columnName) {
            return null;
        }

        $nullable = $this->boolArg($call, 3, false);

        return [$fieldName => $this->columnAttributes($fieldName, $columnName, $typeExpr, null, $nullable, false, [], false, false, null)];
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
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleDateAdded(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $nullable = $this->boolArg($calls[0], 0, false);

        return ['dateAdded' => $this->columnAttributes('dateAdded', 'date_added', new String_('datetime'), null, $nullable, false, [], false, false, null)];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddPublishDates(array $calls): ?array
    {
        if (1 !== count($calls) || [] !== $calls[0]->args) {
            return null;
        }

        return [
            'publishUp'   => $this->columnAttributes('publishUp', 'publish_up', new String_('datetime'), null, true, false, [], false, false, null),
            'publishDown' => $this->columnAttributes('publishDown', 'publish_down', new String_('datetime'), null, true, false, [], false, false, null),
        ];
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

        $target = $create->args[1]->value;

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

        $target = $create->args[1]->value;

        $mappedBy            = null;
        $inversedBy          = null;
        $cascade             = [];
        $fetch               = null;
        $orphanRemoval       = false;
        $indexBy             = null;
        $orderBy             = null;
        $joinTable           = null;
        $joinColumns         = [];
        $inverseJoinColumns  = [];
        $sawBuild            = false;

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
     * addLead($nullable, $onDelete, $isPrimaryKey, $inversedBy) and addContact(...).
     *
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleContactHelper(array $calls, string $fieldName, string $joinColumnName, string $targetFqn): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $call       = $calls[0];
        $nullable   = $this->boolArg($call, 0, false);
        $onDelete   = $this->stringArg($call, 1) ?? 'CASCADE';
        $isPrimary  = $this->boolArg($call, 2, false);
        $inversedBy = $this->stringArg($call, 3);

        $target = new ClassConstFetch(new FullyQualified($targetFqn), new Identifier('class'));

        return [$fieldName => $this->associationAttributes(
            'ManyToOne',
            $target,
            null,
            $inversedBy,
            [],
            null,
            false,
            $isPrimary,
            null,
            null,
            [$this->joinColumn($joinColumnName, 'id', $nullable, false, $onDelete)],
        )];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddCategory(array $calls): ?array
    {
        if (1 !== count($calls) || [] !== $calls[0]->args) {
            return null;
        }

        $target = new ClassConstFetch(new FullyQualified('Mautic\\CategoryBundle\\Entity\\Category'), new Identifier('class'));

        return ['category' => $this->associationAttributes(
            'ManyToOne',
            $target,
            null,
            null,
            ['merge', 'detach'],
            null,
            false,
            false,
            null,
            null,
            [$this->joinColumn('category_id', 'id', true, false, 'SET NULL')],
        )];
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return array<string, list<AttributeGroup>>|null
     */
    private function handleAddIpAddress(array $calls): ?array
    {
        if (1 !== count($calls)) {
            return null;
        }

        $nullable = $this->boolArg($calls[0], 0, false);
        $target   = new ClassConstFetch(new FullyQualified('Mautic\\CoreBundle\\Entity\\IpAddress'), new Identifier('class'));

        return ['ipAddress' => $this->associationAttributes(
            'ManyToOne',
            $target,
            null,
            null,
            ['persist', 'merge', 'detach'],
            null,
            false,
            false,
            null,
            null,
            [$this->joinColumn('ip_id', 'id', $nullable, false, 'SET NULL')],
        )];
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
    private function attribute(string $shortName, array $args): AttributeGroup
    {
        return new AttributeGroup([new Attribute(new Name('ORM\\'.$shortName), array_values($args))]);
    }

    private function namedArg(string $name, Expr $value): Arg
    {
        return new Arg($value, false, false, [], new Identifier($name));
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

    private function methodName(MethodCall $call): string
    {
        return $call->name instanceof Identifier ? $call->name->toString() : '';
    }

    private function stringArg(MethodCall $call, int $index): ?string
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;

        return $value instanceof String_ ? $value->value : null;
    }

    /**
     * Returns the string value, false when the argument is literal false, or $default when absent.
     */
    private function stringOrFalseArg(MethodCall $call, int $index, string $default): string|false
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return $default;
        }

        $value = $call->args[$index]->value;

        if ($value instanceof String_) {
            return $value->value;
        }

        if ($value instanceof ConstFetch && $this->isName($value, 'false')) {
            return false;
        }

        return $default;
    }

    private function intArg(MethodCall $call, int $index): ?int
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        $value = $call->args[$index]->value;

        return $value instanceof Int_ ? $value->value : null;
    }

    private function boolArg(MethodCall $call, int $index, bool $default): bool
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return $default;
        }

        $value = $call->args[$index]->value;

        return $value instanceof ConstFetch && $this->isName($value, 'true');
    }

    private function findProperty(Class_ $class, string $name): ?Property
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $prop) {
                if ($this->isName($prop, $name)) {
                    return $property;
                }
            }
        }

        return null;
    }
}
