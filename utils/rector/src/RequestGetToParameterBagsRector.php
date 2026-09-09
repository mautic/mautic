<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;

/**
 * Replaces the removed Request::get() with explicit parameter bag access.
 *
 * Request::get() resolves values in this order: path parameters (attributes),
 * then the query string, then the request body. The generated expression keeps
 * that precedence:
 *
 * $request->get('key')        => $request->attributes->all()['key'] ?? $request->query->all()['key'] ?? $request->request->all()['key'] ?? null
 * $request->get('key', $d)    => $request->attributes->all()['key'] ?? $request->query->all()['key'] ?? $request->request->all()['key'] ?? $d
 *
 * Calls with a non-literal key are skipped and must be migrated manually.
 */
final class RequestGetToParameterBagsRector extends AbstractRector
{
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isName($node->name, 'get')) {
            return null;
        }

        if (!$this->isObjectType($node->var, new ObjectType('Symfony\Component\HttpFoundation\Request'))) {
            return null;
        }

        if (!isset($node->args[0]) || !$node->args[0] instanceof Arg) {
            return null;
        }

        $keyExpr = $node->args[0]->value;

        if (!$keyExpr instanceof String_) {
            // Dynamic keys need a human to decide which bag(s) apply.
            return null;
        }

        $default = isset($node->args[1]) && $node->args[1] instanceof Arg
            ? $node->args[1]->value
            : new ConstFetch(new Name('null'));

        return new Coalesce(
            $this->createBagArrayFetch($node->var, 'attributes', $keyExpr),
            new Coalesce(
                $this->createBagArrayFetch($node->var, 'query', $keyExpr),
                new Coalesce(
                    $this->createBagArrayFetch($node->var, 'request', $keyExpr),
                    $default
                )
            )
        );
    }

    private function createBagArrayFetch(Node\Expr $requestExpr, string $bag, String_ $keyExpr): ArrayDimFetch
    {
        return new ArrayDimFetch(
            new MethodCall(new PropertyFetch(clone $requestExpr, $bag), 'all'),
            clone $keyExpr
        );
    }
}
