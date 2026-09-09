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
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
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
 * Converts a static loadMetadata() ClassMetadataBuilder mapping into Doctrine attributes.
 *
 * Works statement by statement: every builder call the rule understands becomes an attribute,
 * while anything it does not (isOwnershipParent, custom static helpers such as
 * addTranslationMetadata, a field whose property is not in the class body, ...) stays behind
 * in a trimmed loadMetadata for a follow-up change. A class is left untouched only when no
 * statement could be converted.
 */
final class LoadMetadataToDoctrineAttributeRector extends AbstractRector
{
    /**
     * Mautic's ClassMetadataBuilder caps every string column at this length (UTF8MB4 index limit).
     */
    private const int DEFAULT_STRING_LENGTH = 191;

    private bool $isHybrid = false;

    private bool $hybridHasTable = false;

    private bool $hybridEntityHasRepositoryClass = false;

    /**
     * @var string[]
     */
    private const array CLASS_LEVEL_METHODS = ['setTable', 'setCustomRepositoryClass', 'addIndex', 'addFulltextIndex', 'addUniqueConstraint'];

    /**
     * @var string[]
     */
    private const array FIELD_CREATOR_METHODS = [
        'createField', 'addBigIntIdField', 'addDateAdded', 'addId', 'addIdColumns',
        'addNullableField', 'addNamedField', 'addField', 'addPublishDates',
        'createManyToOne', 'createOneToMany', 'createOneToOne', 'createManyToMany',
        'addLead', 'addContact', 'addCategory', 'addIpAddress',
    ];

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

        // A class may already carry ORM attributes on its properties while class-level and
        // lifecycle mapping stays in loadMetadata. In that hybrid state we convert the leftover
        // calls and merge the generated attributes into the existing ones instead of duplicating.
        $this->isHybrid                       = $this->hasOrmMappingAttribute($node->attrGroups);
        $this->hybridHasTable                 = $this->hasAttributeNamed($node->attrGroups, 'Table');
        $this->hybridEntityHasRepositoryClass = $this->entityHasRepositoryClass($node->attrGroups);

        $result = $this->interpret($loadMetadata->stmts, $node);
        if (null === $result) {
            return null;
        }

        [$classAttributes, $propertyResolved, $newProperties, $builderAssignStatement, $keptStatements, $methodResolved] = $result;

        // Every entity gets DEFERRED_EXPLICIT from the builder constructor.
        $classAttributes[] = $this->attribute('ChangeTrackingPolicy', [new Arg(new String_('DEFERRED_EXPLICIT'))]);

        foreach ($propertyResolved as [$property, $attributeGroups]) {
            $property->attrGroups = array_merge($property->attrGroups, $attributeGroups);
        }

        foreach ($methodResolved as [$method, $attributeGroups]) {
            $method->attrGroups = array_merge($method->attrGroups, $attributeGroups);
        }

        if ($this->isHybrid) {
            $this->mergeClassAttributes($node, $classAttributes);
        } else {
            $node->attrGroups = array_merge($node->attrGroups, $classAttributes);
        }

        if ([] === $keptStatements) {
            // Everything converted: drop loadMetadata entirely.
            $node->stmts = array_values(array_filter(
                $node->stmts,
                static fn (Node $stmt): bool => $stmt !== $loadMetadata
            ));
        } else {
            // Builder calls this rule does not understand (isOwnershipParent, custom static
            // helpers, ...) stay in a trimmed loadMetadata for a follow-up; keep their builder.
            $loadMetadata->stmts = array_values(array_filter([$builderAssignStatement, ...$keptStatements]));
        }

        // Properties whose declaration lives in a trait (e.g. projects) get emitted into the
        // class body so their per-entity mapping can be attached; place them after trait uses.
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
     * Convert every statement the rule understands; leave the rest in loadMetadata. Returns
     * null only when nothing was understood, so the file is left untouched.
     *
     * @param Node\Stmt[] $stmts
     *
     * @return array{list<AttributeGroup>, list<array{0: Property|Param, 1: list<AttributeGroup>}>, list<Property>, ?Expression, list<Expression>, list<array{0: ClassMethod, 1: list<AttributeGroup>}>}|null
     */
    private function interpret(array $stmts, Class_ $node): ?array
    {
        $classAttributes        = [];
        $entityArgs             = [];
        $isMappedSuperclass     = false;
        $newProperties          = [];
        $propertyResolved       = [];
        $methodResolved         = [];
        $builderAssignStatement = null;
        $keptStatements         = [];
        $anyConverted           = false;

        foreach ($stmts as $stmt) {
            $converted = $this->convertStatement($stmt, $node);

            if ('assign' === $converted) {
                $builderAssignStatement = $stmt;

                continue;
            }

            if (null === $converted) {
                // Not understood, or a target property/method is missing: leave the statement
                // in loadMetadata for a follow-up instead of bailing the whole class.
                $keptStatements[] = $stmt;

                continue;
            }

            $classAttributes    = array_merge($classAttributes, $converted['classAttributes']);
            $entityArgs         = array_merge($entityArgs, $converted['entityArgs']);
            $isMappedSuperclass = $isMappedSuperclass || $converted['isMappedSuperclass'];
            $newProperties      = array_merge($newProperties, $converted['newProperties']);

            foreach ($converted['propertyResolved'] as $pair) {
                $propertyResolved[] = $pair;
            }

            foreach ($converted['methodResolved'] as $pair) {
                $methodResolved[] = $pair;
            }

            $anyConverted = true;
        }

        // Nothing understood: leave the file untouched.
        if (!$anyConverted) {
            return null;
        }

        // Lifecycle callbacks on methods require the class-level marker attribute.
        if ([] !== $methodResolved) {
            $classAttributes[] = $this->attribute('HasLifecycleCallbacks', []);
        }

        // The mapping root attribute is mandatory; place it first.
        $rootAttribute = $isMappedSuperclass
            ? $this->attribute('MappedSuperclass', $entityArgs)
            : $this->attribute('Entity', $entityArgs);
        array_unshift($classAttributes, $rootAttribute);

        return [$classAttributes, $propertyResolved, $newProperties, $builderAssignStatement, $keptStatements, $methodResolved];
    }

    /**
     * Convert a single loadMetadata statement. Returns 'assign' for the builder assignment,
     * a conversion struct when fully understood and its targets exist, or null when the
     * statement should stay in loadMetadata.
     *
     * @return 'assign'|array{classAttributes: list<AttributeGroup>, entityArgs: list<Arg>, isMappedSuperclass: bool, newProperties: list<Property>, propertyResolved: list<array{0: Property|Param, 1: list<AttributeGroup>}>, methodResolved: list<array{0: ClassMethod, 1: list<AttributeGroup>}>}|null
     */
    private function convertStatement(Node\Stmt $stmt, Class_ $node): string|array|null
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

        // self::addUuidField($builder) and friends.
        if ($expr instanceof StaticCall) {
            // Hybrid classes already map their own fields via attributes, so a helper that emits a
            // property is left behind; a known no-op helper (its column lives on a trait property
            // that carries its own attribute) is still understood and drops out of loadMetadata.
            if ($this->isHybrid) {
                $fields = $this->handleStaticHelper($expr);
                if (null === $fields) {
                    return null;
                }

                return $this->resolveTargets($fields, [], $node);
            }

            // self::addProjectsField($builder, $table, $column): emit a standalone projects property.
            $projectsProperty = $this->tryProjectsField($expr);
            if ($projectsProperty instanceof Property) {
                $conversion                  = $this->resolveTargets([], [], $node);
                $conversion['newProperties'] = [$projectsProperty];

                return $conversion;
            }

            $fields = $this->handleStaticHelper($expr);
            if (null === $fields) {
                return null;
            }

            return $this->resolveTargets($fields, [], $node);
        }

        if (!$expr instanceof MethodCall) {
            return null;
        }

        $calls = $this->flattenChain($expr);
        if (null === $calls) {
            return null;
        }

        // A single fluent chain may mix class-level and field-level builder calls; split it
        // into per-creator segments so each is interpreted on its own.
        $segments = $this->splitIntoSegments($calls);
        if (null === $segments) {
            return null;
        }

        $classAttributes    = [];
        $entityArgs         = [];
        $isMappedSuperclass = false;
        $propertyAttributes = [];
        $methodAttributes   = [];

        foreach ($segments as $segmentCalls) {
            $first = $this->methodName($segmentCalls[0]);

            // $builder->setMappedSuperClass(): emit #[ORM\MappedSuperclass] instead of #[ORM\Entity].
            if ('setMappedSuperClass' === $first) {
                if (1 !== count($segmentCalls) || [] !== $segmentCalls[0]->args) {
                    return null;
                }

                $isMappedSuperclass = true;

                continue;
            }

            if (in_array($first, self::CLASS_LEVEL_METHODS, true)) {
                $handled = $this->handleClassChain($segmentCalls);
                if (null === $handled) {
                    return null;
                }

                $classAttributes = array_merge($classAttributes, $handled['attributes']);
                $entityArgs      = array_merge($entityArgs, $handled['entityArgs']);

                foreach ($handled['lifecycle'] as $methodName => $attributeGroups) {
                    $methodAttributes[$methodName] = array_merge($methodAttributes[$methodName] ?? [], $attributeGroups);
                }

                continue;
            }

            // Hybrid classes already map their fields via attributes; leave field chains behind.
            if ($this->isHybrid) {
                return null;
            }

            $fields = $this->handleFieldChain($segmentCalls);
            if (null === $fields) {
                return null;
            }

            foreach ($fields as $fieldName => $attributeGroups) {
                $propertyAttributes[$fieldName] = $attributeGroups;
            }
        }

        // A missing target property or method keeps the whole statement in loadMetadata.
        $conversion = $this->resolveTargets($propertyAttributes, $methodAttributes, $node);
        if (null === $conversion) {
            return null;
        }

        $conversion['classAttributes']    = $classAttributes;
        $conversion['entityArgs']         = $entityArgs;
        $conversion['isMappedSuperclass'] = $isMappedSuperclass;

        return $conversion;
    }

    /**
     * Resolves field/method names against the class body. Returns a conversion struct, or null
     * when any target is missing so the caller can keep the statement.
     *
     * @param array<string, list<AttributeGroup>> $propertyAttributes
     * @param array<string, list<AttributeGroup>> $methodAttributes
     *
     * @return array{classAttributes: list<AttributeGroup>, entityArgs: list<Arg>, isMappedSuperclass: bool, newProperties: list<Property>, propertyResolved: list<array{0: Property|Param, 1: list<AttributeGroup>}>, methodResolved: list<array{0: ClassMethod, 1: list<AttributeGroup>}>}|null
     */
    private function resolveTargets(array $propertyAttributes, array $methodAttributes, Class_ $node): ?array
    {
        $propertyResolved = [];
        foreach ($propertyAttributes as $propertyName => $attributeGroups) {
            $property = $this->findProperty($node, $propertyName);
            if (!$property instanceof Property && !$property instanceof Param) {
                return null;
            }

            $propertyResolved[] = [$property, $attributeGroups];
        }

        $methodResolved = [];
        foreach ($methodAttributes as $methodName => $attributeGroups) {
            $method = $node->getMethod($methodName);
            if (!$method instanceof ClassMethod) {
                return null;
            }

            $methodResolved[] = [$method, $attributeGroups];
        }

        return [
            'classAttributes'    => [],
            'entityArgs'         => [],
            'isMappedSuperclass' => false,
            'newProperties'      => [],
            'propertyResolved'   => $propertyResolved,
            'methodResolved'     => $methodResolved,
        ];
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
            null,
            null,
            ['merge', 'persist', 'detach'],
            'LAZY',
            false,
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
     * Splits a flattened builder chain into segments, each headed by a class-level or
     * field-level creator; trailing modifier calls (columnName, build, addJoinColumn, ...)
     * attach to the segment they follow. Null when a modifier precedes any creator.
     *
     * @param list<MethodCall> $calls
     *
     * @return list<list<MethodCall>>|null
     */
    private function splitIntoSegments(array $calls): ?array
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
    private function flattenChain(MethodCall $call): ?array
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

    /**
     * @param list<MethodCall> $calls
     *
     * @return array{attributes: list<AttributeGroup>, entityArgs: list<Arg>, lifecycle: array<string, list<AttributeGroup>>}|null
     */
    private function handleClassChain(array $calls): ?array
    {
        $attributes = [];
        $entityArgs = [];
        $lifecycle  = [];

        foreach ($calls as $call) {
            switch ($this->methodName($call)) {
                case 'setTable':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg) {
                        return null;
                    }

                    // Already mapped by an existing #[ORM\Table]: keep the call for a follow-up.
                    if ($this->isHybrid && $this->hybridHasTable) {
                        return null;
                    }

                    $attributes[] = $this->attribute('Table', [$this->namedArg('name', $call->args[0]->value)]);
                    break;

                case 'setCustomRepositoryClass':
                    if (!isset($call->args[0]) || !$call->args[0] instanceof Arg) {
                        return null;
                    }

                    // Already declared on the existing #[ORM\Entity]: keep the call for a follow-up.
                    if ($this->isHybrid && $this->hybridEntityHasRepositoryClass) {
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

                case 'addUniqueConstraint':
                    if (2 !== count($call->args) || !$call->args[0] instanceof Arg || !$call->args[1] instanceof Arg) {
                        return null;
                    }

                    if (!$call->args[0]->value instanceof Array_) {
                        return null;
                    }

                    $attributes[] = $this->attribute('UniqueConstraint', [
                        $this->namedArg('columns', $call->args[0]->value),
                        $this->namedArg('name', $call->args[1]->value),
                    ]);
                    break;

                case 'addLifecycleEvent':
                    $methodName = $this->stringArg($call, 0);
                    $event      = $this->lifecycleEventArg($call, 1);
                    if (null === $methodName || null === $event) {
                        return null;
                    }

                    $eventShortName = $this->lifecycleEventShortName($event);
                    if (null === $eventShortName) {
                        return null;
                    }

                    $lifecycle[$methodName][] = $this->attribute($eventShortName, []);
                    break;

                default:
                    return null;
            }
        }

        return ['attributes' => $attributes, 'entityArgs' => $entityArgs, 'lifecycle' => $lifecycle];
    }

    private function lifecycleEventShortName(string $event): ?string
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
    private function lifecycleEventArg(MethodCall $call, int $index): ?string
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

        // addUuidField()/addVersionField(): the column lives on a trait property (UuidTrait::$uuid,
        // OptimisticLockTrait::$version) that carries its own #[ORM\Column]. Emit no per-entity
        // attribute (the property is not in the class body, so it cannot be annotated here) and do
        // not bail, so the call drops out of loadMetadata.
        if (in_array($call->name->toString(), ['addUuidField', 'addVersionField'], true)) {
            return [];
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

    /**
     * The short name of an attribute (Entity, Table, ...), stripped of the ORM\ or FQCN prefix.
     */
    private function attributeShortName(Attribute $attr): string
    {
        return $attr->name->getLast();
    }

    /**
     * @param AttributeGroup[] $attrGroups
     */
    private function hasAttributeNamed(array $attrGroups, string $shortName): bool
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
    private function findAttribute(array $attrGroups, array $shortNames): ?Attribute
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
    private function entityHasRepositoryClass(array $attrGroups): bool
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
     * Folds generated class attributes into the ones the class already carries: the repositoryClass
     * merges into the existing #[ORM\Entity], and only attributes not yet present are appended,
     * right after the root attribute for a tidy block.
     *
     * @param list<AttributeGroup> $generated
     */
    private function mergeClassAttributes(Class_ $node, array $generated): void
    {
        $existingRoot = $this->findAttribute($node->attrGroups, ['Entity', 'MappedSuperclass']);

        $toAppend = [];
        foreach ($generated as $attributeGroup) {
            $attr      = $attributeGroup->attrs[0];
            $shortName = $this->attributeShortName($attr);

            if (in_array($shortName, ['Entity', 'MappedSuperclass'], true)) {
                if ($existingRoot instanceof Attribute) {
                    $existingRoot->args = array_merge($existingRoot->args, $attr->args);
                }

                continue;
            }

            if ($this->hasAttributeNamed($node->attrGroups, $shortName)) {
                continue;
            }

            $toAppend[] = $attributeGroup;
        }

        if ([] === $toAppend) {
            return;
        }

        foreach ($node->attrGroups as $index => $attrGroup) {
            if (in_array($this->attributeShortName($attrGroup->attrs[0]), ['Entity', 'MappedSuperclass'], true)) {
                array_splice($node->attrGroups, $index + 1, 0, $toAppend);

                return;
            }
        }

        $node->attrGroups = array_merge($node->attrGroups, $toAppend);
    }

    private function findProperty(Class_ $class, string $name): Property|Param|null
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
}
