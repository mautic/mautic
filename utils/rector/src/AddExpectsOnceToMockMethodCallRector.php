<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;

/**
 * Makes an expectation-less mock method configuration explicit about how often it is called.
 *
 *   $queueMock->method('getPayload')->willReturn('...')
 *   ->
 *   $queueMock->expects($this->once())->method('getPayload')->willReturn('...')
 *
 * A bare method() only stubs a return value and never fails, so a test keeps passing after the
 * production code stops calling the method - or starts calling it in a loop. Adding expects()
 * turns the stub into a verified expectation.
 *
 * Left alone:
 *   - calls that already carry an expects(), e.g. $mock->expects($this->never())->method('run')
 *   - method() on anything that is not a MockObject
 */
final class AddExpectsOnceToMockMethodCallRector extends AbstractRector
{
    private const MOCK_OBJECT = 'PHPUnit\Framework\MockObject\MockObject';

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof MethodCall) {
            return null;
        }

        if (!$this->isName($node->name, 'method')) {
            return null;
        }

        // $mock->expects(...)->method(...) is already explicit
        if ($node->var instanceof MethodCall && $this->isName($node->var->name, 'expects')) {
            return null;
        }

        // method() is also defined on the InvocationMocker returned by expects(), so the mock type
        // check is what separates a bare stub from an already configured expectation
        if (!$this->isObjectType($node->var, new ObjectType(self::MOCK_OBJECT))) {
            return null;
        }

        $onceCall = new MethodCall(new Variable('this'), 'once');

        $node->var = new MethodCall($node->var, 'expects', [new Arg($onceCall)]);

        return $node;
    }
}
