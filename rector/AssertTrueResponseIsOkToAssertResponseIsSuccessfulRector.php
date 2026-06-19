<?php

declare(strict_types=1);

namespace MauticRector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AssertTrueResponseIsOkToAssertResponseIsSuccessfulRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace BrowserKit success assertions with assertResponseIsSuccessful()',
            [
                new CodeSample(
                    'Assert::assertTrue($this->client->getResponse()->isOk());',
                    'self::assertResponseIsSuccessful();'
                ),
                new CodeSample(
                    '$this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());',
                    '$this->assertResponseIsSuccessful($this->client->getResponse()->getContent());'
                ),
                new CodeSample(
                    '$clientResponse = $this->client->getResponse();'."\n".'$this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());',
                    '$clientResponse = $this->client->getResponse();'."\n".'$this->assertResponseIsSuccessful($clientResponse->getContent());'
                ),
                new CodeSample(
                    '$clientResponse = $this->client->getResponse();'."\n".'$this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());',
                    '$clientResponse = $this->client->getResponse();'."\n".'$this->assertResponseIsSuccessful();'
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class, StaticCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        if (!$this->isInWebTestCase($node)) {
            return null;
        }

        $assertionType = $this->resolveAssertionType($node);
        if (null === $assertionType) {
            return null;
        }

        $args = $this->resolveReplacementArgs($node, $assertionType);
        if (null === $args) {
            return null;
        }

        if ($node instanceof MethodCall) {
            return new MethodCall($node->var, new Identifier('assertResponseIsSuccessful'), $args);
        }

        return new StaticCall(new Name('self'), 'assertResponseIsSuccessful', $args);
    }

    private function resolveAssertionType(Node $node): ?string
    {
        if ($this->isName($node->name, 'assertTrue')) {
            return 'assertTrue';
        }

        if ($this->isName($node->name, 'assertEquals') || $this->isName($node->name, 'assertSame')) {
            return 'statusCode';
        }

        return null;
    }

    /**
     * @return Arg[]|null
     */
    private function resolveReplacementArgs(Node $node, string $assertionType): ?array
    {
        if ('assertTrue' === $assertionType) {
            if (!isset($node->args[0]) || !$this->isBrowserKitResponseOkCheck($node->args[0], $node)) {
                return null;
            }

            return isset($node->args[1]) && $node->args[1] instanceof Arg
                ? [$node->args[1]]
                : [];
        }

        if (!isset($node->args[0], $node->args[1])) {
            return null;
        }

        if (!$this->isHttpOkValue($node->args[0]) || !$this->isBrowserKitStatusCodeCheck($node->args[1], $node)) {
            return null;
        }

        return isset($node->args[2]) && $node->args[2] instanceof Arg
            ? [$node->args[2]]
            : [];
    }

    private function isBrowserKitResponseOkCheck(Arg $arg, Node $assertNode): bool
    {
        $value = $arg->value;

        if (!$value instanceof MethodCall || !$this->isName($value->name, 'isOk')) {
            return false;
        }

        return $this->isBrowserKitResponseFetch($value->var)
            || $this->isVariableAssignedFromBrowserKitResponse($value->var, $assertNode);
    }

    private function isBrowserKitStatusCodeCheck(Arg $arg, Node $assertNode): bool
    {
        $value = $arg->value;

        if (!$value instanceof MethodCall || !$this->isName($value->name, 'getStatusCode')) {
            return false;
        }

        return $this->isBrowserKitResponseFetch($value->var)
            || $this->isVariableAssignedFromBrowserKitResponse($value->var, $assertNode);
    }

    private function isHttpOkValue(Arg $arg): bool
    {
        $value = $arg->value;

        if ($value instanceof Int_) {
            return 200 === $value->value;
        }

        if (!$value instanceof ClassConstFetch) {
            return false;
        }

        return $this->isName($value->class, 'Response')
            && $this->isName($value->name, 'HTTP_OK');
    }

    private function isBrowserKitResponseFetch(Node $node): bool
    {
        if (!$node instanceof MethodCall || !$this->isName($node->name, 'getResponse')) {
            return false;
        }

        $clientFetch = $node->var;

        return $clientFetch instanceof PropertyFetch
            && $this->isName($clientFetch->var, 'this')
            && $this->isName($clientFetch->name, 'client');
    }

    private function isVariableAssignedFromBrowserKitResponse(Node $node, Node $assertNode): bool
    {
        if (!$node instanceof Variable || !is_string($node->name)) {
            return false;
        }

        if ('clientResponse' === $node->name) {
            return true;
        }

        $currentStmt = $this->findCurrentStatement($assertNode);
        if (!$currentStmt instanceof Stmt) {
            return false;
        }

        $parentNode = $currentStmt->getAttribute('parent');
        if (!$parentNode instanceof Node || !property_exists($parentNode, 'stmts') || !is_array($parentNode->stmts)) {
            return false;
        }

        $stmtKey = $this->resolveStatementKey($parentNode->stmts, $currentStmt);
        if (0 === $stmtKey) {
            return false;
        }

        for ($currentKey = $stmtKey - 1; $currentKey >= 0; --$currentKey) {
            $previousStmt = $parentNode->stmts[$currentKey] ?? null;
            if (!$previousStmt instanceof Expression || !$previousStmt->expr instanceof Assign) {
                continue;
            }

            $assign = $previousStmt->expr;
            if (!$assign->var instanceof Variable || !is_string($assign->var->name) || $assign->var->name !== $node->name) {
                continue;
            }

            return $this->isBrowserKitResponseFetch($assign->expr);
        }

        return false;
    }

    /**
     * @param Stmt[] $stmts
     */
    private function resolveStatementKey(array $stmts, Stmt $currentStmt): ?int
    {
        foreach ($stmts as $key => $stmt) {
            if ($stmt === $currentStmt) {
                return $key;
            }
        }

        return null;
    }

    private function findCurrentStatement(Node $node): ?Stmt
    {
        $currentNode = $node;

        while (!$currentNode instanceof Stmt) {
            $currentNode = $currentNode->getAttribute('parent');
            if (!$currentNode instanceof Node) {
                return null;
            }
        }

        return $currentNode;
    }

    private function isInWebTestCase(Node $node): bool
    {
        $classReflection = ScopeFetcher::fetch($node)->getClassReflection();

        return null !== $classReflection
            && $classReflection->is('Symfony\Bundle\FrameworkBundle\Test\WebTestCase');
    }
}
