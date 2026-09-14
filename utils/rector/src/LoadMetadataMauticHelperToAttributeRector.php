<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;

/**
 * Rewrites Mautic's ClassMetadataBuilder convenience helpers in loadMetadata (addId,
 * addBigIntIdField, addPublishDates, addContact, addFulltextIndex, ...) into the equivalent native
 * builder calls they wrap. LoadMetadataToDoctrineAttributeRector then turns those native calls into
 * attributes, so only vanilla Doctrine vocabulary reaches the first rule.
 *
 * Run this rule before LoadMetadataToDoctrineAttributeRector.
 */
final class LoadMetadataMauticHelperToAttributeRector extends AbstractRector
{
    /**
     * Field and association helpers, each a standalone builder call that becomes one or more native
     * createField()/createManyToOne() statements.
     *
     * @var string[]
     */
    private const array FIELD_HELPERS = [
        'addId', 'addBigIntIdField', 'addIdColumns', 'addDateAdded', 'addPublishDates',
        'addNullableField', 'addNamedField', 'addLead', 'addContact', 'addCategory', 'addIpAddress',
    ];

    /**
     * Every builder call that heads a segment; trailing modifiers (columnName, addJoinColumn, ...)
     * belong to the segment they follow.
     *
     * @var string[]
     */
    private const array SEGMENT_STARTERS = [
        'setTable', 'setCustomRepositoryClass', 'setMappedSuperClass', 'addIndex', 'addFulltextIndex',
        'addIndexWithOptions', 'addUniqueConstraint',
        'createField', 'addField', 'createManyToOne', 'createOneToMany', 'createOneToOne', 'createManyToMany',
        'addId', 'addBigIntIdField', 'addIdColumns', 'addDateAdded', 'addPublishDates',
        'addNullableField', 'addNamedField', 'addLead', 'addContact', 'addCategory', 'addIpAddress',
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

        // Entities extending a FOS OAuth server model keep their loadMetadata() mapping untouched.
        if ($node->extends instanceof Name
            && str_starts_with((string) $this->getName($node->extends), 'FOS\\OAuthServerBundle\\Model\\')
        ) {
            return null;
        }

        // In an already attribute-mapped class the leftover field helpers are redundant; leave them
        // (and the class) alone. Class-level index helpers are still rewritten below.
        $isHybrid = $this->hasClassLevelOrmAttribute($node);

        $newStmts = [];
        $changed  = false;

        foreach ($loadMetadata->stmts as $stmt) {
            if (!$isHybrid && $stmt instanceof Expression && $this->containsFieldHelper($stmt)) {
                foreach ($this->unchainAndDesugar($stmt) as $produced) {
                    $newStmts[] = $produced;
                }

                $changed = true;

                continue;
            }

            // addFulltextIndex()/addIndexWithOptions() sit inside a fluent class-level chain; rewrite
            // them to native addIndex() in place so the chain stays a single statement.
            if ($this->rewriteIndexHelpers($stmt)) {
                $changed = true;
            }

            $newStmts[] = $stmt;
        }

        if (!$changed) {
            return null;
        }

        $loadMetadata->stmts = $newStmts;

        return $node;
    }

    private function containsFieldHelper(Expression $stmt): bool
    {
        $current = $stmt->expr;
        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier && in_array($current->name->toString(), self::FIELD_HELPERS, true)) {
                return true;
            }

            $current = $current->var;
        }

        return false;
    }

    /**
     * Split the statement's builder chain into per-starter segments; desugar the Mautic field/assoc
     * segments and re-emit every other segment as its own native statement.
     *
     * @return list<Expression>
     */
    private function unchainAndDesugar(Expression $stmt): array
    {
        $expr = $stmt->expr;

        $calls = $this->flattenChain($expr);
        $root  = $this->chainRoot($expr);
        if (null === $calls || !$root instanceof Variable) {
            return [$stmt];
        }

        $segments = $this->splitIntoSegments($calls);
        if (null === $segments) {
            return [new Expression($expr)];
        }

        $produced = [];
        foreach ($segments as $segment) {
            $starter = $this->methodName($segment[0]);

            if (1 === count($segment) && in_array($starter, self::FIELD_HELPERS, true)) {
                foreach ($this->desugarFieldHelper($root, $segment[0]) as $desugared) {
                    $produced[] = $desugared;
                }

                continue;
            }

            // Native segment: rebuild it as its own statement and rewrite index helpers in place.
            $rebuilt = $this->rebuildSegment($root, $segment);
            $this->rewriteIndexHelpers($rebuilt);
            $produced[] = $rebuilt;
        }

        return $produced;
    }

    /**
     * @return list<Expression>
     */
    private function desugarFieldHelper(Variable $builder, MethodCall $call): array
    {
        return match ($this->methodName($call)) {
            'addId'            => [$this->buildAddId($builder)],
            'addBigIntIdField' => [$this->buildBigIntIdField($builder, $call)],
            'addIdColumns'     => $this->buildAddIdColumns($builder, $call),
            'addDateAdded'     => [$this->buildDateAdded($builder, $call)],
            'addPublishDates'  => $this->buildPublishDates($builder),
            'addNullableField' => [$this->buildNullableField($builder, $call)],
            'addNamedField'    => [$this->buildNamedField($builder, $call)],
            'addLead'          => [$this->buildContactHelper($builder, $call, 'lead', 'lead_id', 'Mautic\\LeadBundle\\Entity\\Lead')],
            'addContact'       => [$this->buildContactHelper($builder, $call, 'contact', 'contact_id', 'Mautic\\LeadBundle\\Entity\\Lead')],
            'addCategory'      => [$this->buildCategory($builder)],
            'addIpAddress'     => [$this->buildIpAddress($builder, $call)],
            default            => [new Expression($call)],
        };
    }

    private function buildAddId(Variable $builder): Expression
    {
        return $this->chain($builder, [
            $this->step('createField', [$this->strArg('id'), $this->strArg('integer')]),
            $this->step('makePrimaryKey', []),
            $this->step('generatedValue', []),
            $this->step('option', [$this->strArg('unsigned'), $this->boolArg(true)]),
            $this->step('build', []),
        ]);
    }

    private function buildBigIntIdField(Variable $builder, MethodCall $call): Expression
    {
        $fieldName  = $this->stringArg($call, 0) ?? 'id';
        $columnName = $this->stringArg($call, 1) ?? 'id';
        $isPrimary  = $this->boolArgValue($call, 2, true);
        $isNullable = $this->boolArgValue($call, 3, false);

        $steps = [
            $this->step('createField', [$this->strArg($fieldName), $this->strArg('bigint')]),
            $this->step('columnName', [$this->strArg($columnName)]),
        ];

        if ($isPrimary) {
            $steps[] = $this->step('makePrimaryKey', []);
            $steps[] = $this->step('generatedValue', []);
        } elseif ($isNullable) {
            $steps[] = $this->step('nullable', []);
        }

        $steps[] = $this->step('option', [$this->strArg('unsigned'), $this->boolArg(true)]);
        $steps[] = $this->step('build', []);

        return $this->chain($builder, $steps);
    }

    /**
     * @return list<Expression>
     */
    private function buildAddIdColumns(Variable $builder, MethodCall $call): array
    {
        $nameColumn = $this->stringOrFalseArg($call, 0, 'name');
        $descColumn = $this->stringOrFalseArg($call, 1, 'description');

        $statements = [$this->buildAddId($builder)];

        if (is_string($nameColumn)) {
            $statements[] = $this->chain($builder, [
                $this->step('createField', [$this->strArg($nameColumn), $this->strArg('string')]),
                $this->step('build', []),
            ]);
        }

        if (is_string($descColumn)) {
            $statements[] = $this->chain($builder, [
                $this->step('createField', [$this->strArg($descColumn), $this->strArg('text')]),
                $this->step('nullable', []),
                $this->step('build', []),
            ]);
        }

        return $statements;
    }

    private function buildDateAdded(Variable $builder, MethodCall $call): Expression
    {
        $steps = [
            $this->step('createField', [$this->strArg('dateAdded'), $this->strArg('datetime')]),
            $this->step('columnName', [$this->strArg('date_added')]),
        ];

        if ($this->boolArgValue($call, 0, false)) {
            $steps[] = $this->step('nullable', []);
        }

        $steps[] = $this->step('build', []);

        return $this->chain($builder, $steps);
    }

    /**
     * @return list<Expression>
     */
    private function buildPublishDates(Variable $builder): array
    {
        return [
            $this->chain($builder, [
                $this->step('createField', [$this->strArg('publishUp'), $this->strArg('datetime')]),
                $this->step('columnName', [$this->strArg('publish_up')]),
                $this->step('nullable', []),
                $this->step('build', []),
            ]),
            $this->chain($builder, [
                $this->step('createField', [$this->strArg('publishDown'), $this->strArg('datetime')]),
                $this->step('columnName', [$this->strArg('publish_down')]),
                $this->step('nullable', []),
                $this->step('build', []),
            ]),
        ];
    }

    private function buildNullableField(Variable $builder, MethodCall $call): Expression
    {
        $name       = $this->stringArg($call, 0) ?? '';
        $typeExpr   = $this->argValue($call, 1) ?? new String_('string');
        $columnName = $this->stringArg($call, 2);

        $steps = [
            $this->step('createField', [$this->strArg($name), new Arg($typeExpr)]),
            $this->step('nullable', []),
        ];

        if (null !== $columnName) {
            $steps[] = $this->step('columnName', [$this->strArg($columnName)]);
        }

        $steps[] = $this->step('build', []);

        return $this->chain($builder, $steps);
    }

    private function buildNamedField(Variable $builder, MethodCall $call): Expression
    {
        $name       = $this->stringArg($call, 0) ?? '';
        $typeExpr   = $this->argValue($call, 1) ?? new String_('string');
        $columnName = $this->stringArg($call, 2) ?? '';

        $steps = [
            $this->step('createField', [$this->strArg($name), new Arg($typeExpr)]),
            $this->step('columnName', [$this->strArg($columnName)]),
        ];

        if ($this->boolArgValue($call, 3, false)) {
            $steps[] = $this->step('nullable', []);
        }

        $steps[] = $this->step('build', []);

        return $this->chain($builder, $steps);
    }

    private function buildContactHelper(Variable $builder, MethodCall $call, string $fieldName, string $joinColumnName, string $targetFqn): Expression
    {
        $nullable   = $this->boolArgValue($call, 0, false);
        $onDelete   = $this->stringArg($call, 1) ?? 'CASCADE';
        $isPrimary  = $this->boolArgValue($call, 2, false);
        $inversedBy = $this->stringArg($call, 3);

        $steps = [
            $this->step('createManyToOne', [$this->strArg($fieldName), $this->classConstArg($targetFqn)]),
        ];

        if ($isPrimary) {
            $steps[] = $this->step('makePrimaryKey', []);
        }

        if (null !== $inversedBy) {
            $steps[] = $this->step('inversedBy', [$this->strArg($inversedBy)]);
        }

        $steps[] = $this->step('addJoinColumn', [
            $this->strArg($joinColumnName),
            $this->strArg('id'),
            $this->boolArg($nullable),
            $this->boolArg(false),
            $this->strArg($onDelete),
        ]);
        $steps[] = $this->step('build', []);

        return $this->chain($builder, $steps);
    }

    private function buildCategory(Variable $builder): Expression
    {
        return $this->chain($builder, [
            $this->step('createManyToOne', [$this->strArg('category'), $this->classConstArg('Mautic\\CategoryBundle\\Entity\\Category')]),
            $this->step('cascadeMerge', []),
            $this->step('cascadeDetach', []),
            $this->step('addJoinColumn', [
                $this->strArg('category_id'),
                $this->strArg('id'),
                $this->boolArg(true),
                $this->boolArg(false),
                $this->strArg('SET NULL'),
            ]),
            $this->step('build', []),
        ]);
    }

    private function buildIpAddress(Variable $builder, MethodCall $call): Expression
    {
        $nullable = $this->boolArgValue($call, 0, false);

        return $this->chain($builder, [
            $this->step('createManyToOne', [$this->strArg('ipAddress'), $this->classConstArg('Mautic\\CoreBundle\\Entity\\IpAddress')]),
            $this->step('cascadePersist', []),
            $this->step('cascadeMerge', []),
            $this->step('cascadeDetach', []),
            $this->step('addJoinColumn', [
                $this->strArg('ip_id'),
                $this->strArg('id'),
                $this->boolArg($nullable),
                $this->boolArg(false),
                $this->strArg('SET NULL'),
            ]),
            $this->step('build', []),
        ]);
    }

    /**
     * Rewrites addFulltextIndex()/addIndexWithOptions() calls anywhere in the statement to the native
     * addIndex(columns, name, flags, options) they expand to. Returns whether anything changed.
     */
    private function rewriteIndexHelpers(Node\Stmt $stmt): bool
    {
        if (!$stmt instanceof Expression) {
            return false;
        }

        $changed = false;
        $current = $stmt->expr;

        while ($current instanceof MethodCall) {
            if ($current->name instanceof Identifier) {
                if ('addFulltextIndex' === $current->name->toString()) {
                    $current->name = new Identifier('addIndex');
                    $current->args = [
                        ...$current->args,
                        new Arg(new Array_([new ArrayItem(new String_('fulltext'))])),
                    ];
                    $changed = true;
                } elseif ('addIndexWithOptions' === $current->name->toString() && 3 === count($current->args)) {
                    // addIndex(columns, name, null flags, options).
                    $options       = $current->args[2];
                    $current->args = [
                        $current->args[0],
                        $current->args[1],
                        new Arg(new ConstFetch(new Name('null'))),
                        $options,
                    ];
                    $current->name = new Identifier('addIndex');
                    $changed       = true;
                }
            }

            $current = $current->var;
        }

        return $changed;
    }

    /**
     * Rebuild a segment as a fresh $builder->a()->b() chain statement.
     *
     * @param list<MethodCall> $segment
     */
    private function rebuildSegment(Variable $builder, array $segment): Expression
    {
        $steps = [];
        foreach ($segment as $call) {
            $steps[] = $this->step($this->methodName($call), $call->args);
        }

        return $this->chain($builder, $steps);
    }

    /**
     * @return list<MethodCall>|null
     */
    private function flattenChain(Expr $expr): ?array
    {
        $calls   = [];
        $current = $expr;

        while ($current instanceof MethodCall) {
            $calls[] = $current;
            $current = $current->var;
        }

        if (!$current instanceof Variable) {
            return null;
        }

        return array_reverse($calls);
    }

    private function chainRoot(Expr $expr): Expr
    {
        $current = $expr;
        while ($current instanceof MethodCall) {
            $current = $current->var;
        }

        return $current;
    }

    /**
     * @param list<MethodCall> $calls
     *
     * @return list<list<MethodCall>>|null
     */
    private function splitIntoSegments(array $calls): ?array
    {
        $segments = [];
        $current  = null;

        foreach ($calls as $call) {
            if (in_array($this->methodName($call), self::SEGMENT_STARTERS, true)) {
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
     * @param list<array{0: string, 1: array<Arg|Node\VariadicPlaceholder>}> $steps
     */
    private function chain(Variable $builder, array $steps): Expression
    {
        $expr = new Variable($builder->name);

        foreach ($steps as [$name, $args]) {
            $expr = new MethodCall($expr, new Identifier($name), $args);
        }

        return new Expression($expr);
    }

    /**
     * @param array<Arg|Node\VariadicPlaceholder> $args
     *
     * @return array{0: string, 1: array<Arg|Node\VariadicPlaceholder>}
     */
    private function step(string $name, array $args): array
    {
        return [$name, $args];
    }

    private function strArg(string $value): Arg
    {
        return new Arg(new String_($value));
    }

    private function boolArg(bool $value): Arg
    {
        return new Arg(new ConstFetch(new Name($value ? 'true' : 'false')));
    }

    private function classConstArg(string $fqn): Arg
    {
        return new Arg(new ClassConstFetch(new FullyQualified($fqn), new Identifier('class')));
    }

    private function methodName(MethodCall $call): string
    {
        return $call->name instanceof Identifier ? $call->name->toString() : '';
    }

    private function argValue(MethodCall $call, int $index): ?Expr
    {
        if (!isset($call->args[$index]) || !$call->args[$index] instanceof Arg) {
            return null;
        }

        return $call->args[$index]->value;
    }

    private function stringArg(MethodCall $call, int $index): ?string
    {
        $value = $this->argValue($call, $index);

        return $value instanceof String_ ? $value->value : null;
    }

    private function boolArgValue(MethodCall $call, int $index, bool $default): bool
    {
        $value = $this->argValue($call, $index);
        if (!$value instanceof ConstFetch) {
            return $default;
        }

        return 'true' === $value->name->toString();
    }

    private function stringOrFalseArg(MethodCall $call, int $index, string $default): string|false
    {
        $value = $this->argValue($call, $index);

        if ($value instanceof String_) {
            return $value->value;
        }

        if ($value instanceof ConstFetch && 'false' === $value->name->toString()) {
            return false;
        }

        return $default;
    }

    private function hasClassLevelOrmAttribute(Class_ $class): bool
    {
        foreach ($class->attrGroups as $attrGroup) {
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
