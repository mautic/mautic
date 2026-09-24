<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use Rector\PhpParser\AstResolver;
use Rector\Rector\AbstractRector;

/**
 * In test cases, replaces getRepository(SomeEntity::class) with the concrete repository service
 * pulled from the container:
 *
 *   $this->em->getRepository(WebhookQueue::class)  ->  $this->getContainer()->get(WebhookQueueRepository::class)
 *
 * The generic getRepository() only resolves to EntityRepository<SomeEntity>, so every custom
 * repository method looks undefined to static analysis. Fetching the repository service directly
 * restores the real type. A test cannot take the service through its PHPUnit-owned constructor,
 * so the container lookup is used instead.
 *
 * The entity -> repository mapping is read from the entity's #[ORM\Entity(repositoryClass: ...)]
 * attribute. Entities that do not declare a custom repository class are skipped - there is no
 * concrete service to depend on.
 */
final class TestGetRepositoryToContainerGetRector extends AbstractRector
{
    private const string KERNEL_TEST_CASE = \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class;

    public function __construct(
        private readonly AstResolver $astResolver,
        private readonly NodeFinder $nodeFinder,
    ) {
    }

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

        if ($node->isAbstract() || $node->isAnonymous()) {
            return null;
        }

        // Only test cases - the container lookup is available there.
        if (!$this->isObjectType($node, new ObjectType(self::KERNEL_TEST_CASE))) {
            return null;
        }

        $getRepositoryCalls = $this->findGetRepositoryCalls($node);
        if ([] === $getRepositoryCalls) {
            return null;
        }

        $hasChanged = false;

        foreach ($getRepositoryCalls as $getRepositoryCall) {
            $repositoryClass = $this->resolveRepositoryClass($getRepositoryCall);
            if (null === $repositoryClass) {
                continue;
            }

            $this->replaceNode($node, $getRepositoryCall, $this->createContainerGet($repositoryClass));
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Collects every getRepository(SomeEntity::class) call inside the class, on any receiver.
     *
     * @return MethodCall[]
     */
    private function findGetRepositoryCalls(Class_ $class): array
    {
        /** @var MethodCall[] $methodCalls */
        $methodCalls = $this->nodeFinder->findInstanceOf($class, MethodCall::class);

        return array_values(array_filter($methodCalls, function (MethodCall $methodCall): bool {
            if (!$this->isName($methodCall->name, 'getRepository')) {
                return false;
            }

            // A dynamic argument (a variable, a string) cannot be resolved statically.
            return 1 === count($methodCall->args) && null !== $this->resolveEntityClass($methodCall);
        }));
    }

    /**
     * Reads the entity class name out of getRepository(SomeEntity::class).
     */
    private function resolveEntityClass(MethodCall $methodCall): ?string
    {
        $firstArg = $methodCall->args[0] ?? null;
        if (!$firstArg instanceof Arg) {
            return null;
        }

        if (!$firstArg->value instanceof ClassConstFetch) {
            return null;
        }

        if (!$this->isName($firstArg->value->name, 'class')) {
            return null;
        }

        $className = $this->getName($firstArg->value->class);

        return '' === (string) $className ? null : $className;
    }

    /**
     * The repositoryClass argument of the entity's #[ORM\Entity(repositoryClass: SomeRepository::class)].
     */
    private function resolveRepositoryClass(MethodCall $methodCall): ?string
    {
        $entityClass = $this->resolveEntityClass($methodCall);
        if (null === $entityClass) {
            return null;
        }

        $class = $this->astResolver->resolveClassFromName($entityClass);
        if (!$class instanceof Class_) {
            return null;
        }

        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ('Entity' !== $attr->name->getLast()) {
                    continue;
                }

                foreach ($attr->args as $arg) {
                    if (!$arg instanceof Arg || !$arg->name instanceof Identifier || 'repositoryClass' !== $arg->name->toString()) {
                        continue;
                    }

                    if (!$arg->value instanceof ClassConstFetch) {
                        continue;
                    }

                    $repositoryClass = $this->getName($arg->value->class);
                    if ('' !== (string) $repositoryClass) {
                        return $repositoryClass;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Builds $this->getContainer()->get(SomeRepository::class).
     */
    private function createContainerGet(string $repositoryClass): MethodCall
    {
        return new MethodCall(
            new MethodCall(new Variable('this'), 'getContainer'),
            'get',
            [new Arg(new ClassConstFetch(new FullyQualified($repositoryClass), 'class'))]
        );
    }

    /**
     * Swaps the matched call for its replacement, in place, wherever it sits in the class.
     */
    private function replaceNode(Class_ $class, MethodCall $oldNode, MethodCall $newNode): void
    {
        $this->traverseNodesWithCallable($class, static fn (Node $node): ?Node => $node === $oldNode ? $newNode : null);
    }
}
