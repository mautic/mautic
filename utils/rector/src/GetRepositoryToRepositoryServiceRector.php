<?php

declare(strict_types=1);

namespace Utils\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PHPStan\Type\ObjectType;
use Rector\NodeManipulator\ClassDependencyManipulator;
use Rector\PhpParser\AstResolver;
use Rector\PostRector\ValueObject\PropertyMetadata;
use Rector\Rector\AbstractRector;

/**
 * Replaces $em->getRepository(SomeEntity::class) with the concrete repository service.
 *
 * Without ORM metadata the generic getRepository() only ever resolves to
 * EntityRepository<SomeEntity>, so every custom repository method looks undefined to
 * static analysis. Depending on the repository service directly restores the real type.
 *
 * Two target shapes, picked from the surrounding class:
 *
 *   1. Test cases (Symfony KernelTestCase descendants) get the container lookup:
 *        $this->em->getRepository(Hit::class)  ->  self::getContainer()->get(HitRepository::class)
 *
 *   2. Everything else gets constructor injection and a property fetch:
 *        $this->em->getRepository(Hit::class)  ->  $this->hitRepository
 *      with "private readonly HitRepository $hitRepository" added to the constructor.
 *
 * The entity -> repository mapping is read from the entity's own loadMetadata(), which is
 * where Mautic declares it via $builder->setCustomRepositoryClass(...). Entities that do
 * not declare a custom repository class are skipped - there is no concrete service to
 * depend on, so the generic EntityRepository is already correct.
 */
final class GetRepositoryToRepositoryServiceRector extends AbstractRector
{
    /**
     * Any class extending this is treated as a test and gets the container lookup.
     */
    private const KERNEL_TEST_CASE = 'Symfony\Bundle\FrameworkBundle\Test\KernelTestCase';

    private const ENTITY_MANAGER = 'Doctrine\ORM\EntityManagerInterface';

    public function __construct(
        private readonly AstResolver $astResolver,
        private readonly ClassDependencyManipulator $classDependencyManipulator,
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

        $getRepositoryCalls = $this->findGetRepositoryCalls($node);
        if ([] === $getRepositoryCalls) {
            return null;
        }

        $isTestCase = $this->isObjectType($node, new ObjectType(self::KERNEL_TEST_CASE));
        $hasChanged = false;

        foreach ($getRepositoryCalls as $getRepositoryCall) {
            $repositoryClass = $this->resolveRepositoryClass($getRepositoryCall);
            if (null === $repositoryClass) {
                continue;
            }

            $replacement = $isTestCase
                ? $this->createContainerGet($repositoryClass)
                : $this->createInjectedPropertyFetch($node, $repositoryClass);

            $this->replaceNode($node, $getRepositoryCall, $replacement);
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Collects every $someEntityManager->getRepository(SomeEntity::class) call inside the class.
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

            // Only the Doctrine entity manager - other objects may have their own getRepository().
            if (!$this->isObjectType($methodCall->var, new ObjectType(self::ENTITY_MANAGER))) {
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
     * Finds the repository class the entity declares via
     * $builder->setCustomRepositoryClass(SomeRepository::class) inside loadMetadata().
     */
    private function resolveRepositoryClass(MethodCall $methodCall): ?string
    {
        $entityClass = $this->resolveEntityClass($methodCall);
        if (null === $entityClass) {
            return null;
        }

        $loadMetadataClassMethod = $this->astResolver->resolveClassMethod($entityClass, 'loadMetadata');
        if (!$loadMetadataClassMethod instanceof ClassMethod) {
            return null;
        }

        /** @var MethodCall[] $innerCalls */
        $innerCalls = $this->nodeFinder->findInstanceOf($loadMetadataClassMethod, MethodCall::class);

        foreach ($innerCalls as $innerCall) {
            if (!$this->isName($innerCall->name, 'setCustomRepositoryClass')) {
                continue;
            }

            $firstArg = $innerCall->args[0] ?? null;
            if (!$firstArg instanceof Arg || !$firstArg->value instanceof ClassConstFetch) {
                continue;
            }

            $repositoryClass = $this->getName($firstArg->value->class);
            if ('' !== (string) $repositoryClass) {
                return $repositoryClass;
            }
        }

        return null;
    }

    /**
     * Builds self::getContainer()->get(SomeRepository::class).
     */
    private function createContainerGet(string $repositoryClass): MethodCall
    {
        return new MethodCall(
            new StaticCall(new Name('self'), 'getContainer'),
            'get',
            [new Arg(new ClassConstFetch(new FullyQualified($repositoryClass), 'class'))]
        );
    }

    /**
     * Adds the repository to the constructor if it is not injected yet and returns $this->someRepository.
     */
    private function createInjectedPropertyFetch(Class_ $class, string $repositoryClass): PropertyFetch
    {
        $propertyName = $this->resolvePropertyName($repositoryClass);

        if ($this->shouldUseAutowireMethod($class)) {
            $this->addAutowireDependency($class, $repositoryClass, $propertyName);
        } else {
            $this->classDependencyManipulator->addConstructorDependency(
                $class,
                new PropertyMetadata($propertyName, new ObjectType($repositoryClass))
            );
        }

        return new PropertyFetch(new Variable('this'), $propertyName);
    }

    /**
     * A class without its own constructor that inherits one cannot gain a promoted property without
     * declaring a full constructor override that forwards every parent argument. Mautic injects into
     * such classes with an autowire method instead.
     */
    private function shouldUseAutowireMethod(Class_ $class): bool
    {
        // Its own constructor can take one more promoted parameter safely.
        if ($class->getMethod('__construct') instanceof ClassMethod) {
            return false;
        }

        if (!$class->extends instanceof Name) {
            return false;
        }

        $parentClass = $this->getName($class->extends);
        if ('' === (string) $parentClass) {
            return false;
        }

        // Only an inherited constructor would have to be overridden.
        return $this->astResolver->resolveClassMethod($parentClass, '__construct') instanceof ClassMethod;
    }

    /**
     * Injects through "autowire<ShortClassName>()", reusing that method when the class already has one.
     */
    private function addAutowireDependency(Class_ $class, string $repositoryClass, string $propertyName): void
    {
        // Already injected by an earlier call for the same repository.
        if ($class->getProperty($propertyName) instanceof Property) {
            return;
        }

        $param      = new Param(
            new Variable($propertyName),
            null,
            new FullyQualified($repositoryClass)
        );
        $assignment = new Expression(
            new Assign(new PropertyFetch(new Variable('this'), $propertyName), new Variable($propertyName))
        );

        $autowireClassMethod = $this->resolveAutowireClassMethod($class);

        if ($autowireClassMethod instanceof ClassMethod) {
            $autowireClassMethod->params[] = $param;
            $autowireClassMethod->stmts[]  = $assignment;
        } else {
            $autowireClassMethod = $this->createAutowireClassMethod($class, $param, $assignment);
            array_unshift($class->stmts, $autowireClassMethod);
        }

        array_unshift($class->stmts, $this->createProperty($repositoryClass, $propertyName));
    }

    private function resolveAutowireClassMethod(Class_ $class): ?ClassMethod
    {
        $autowireMethodName = $this->resolveAutowireMethodName($class);

        foreach ($class->getMethods() as $classMethod) {
            if ($this->isName($classMethod->name, $autowireMethodName)) {
                return $classMethod;
            }
        }

        return null;
    }

    private function createAutowireClassMethod(Class_ $class, Param $param, Expression $assignment): ClassMethod
    {
        $classMethod             = new ClassMethod($this->resolveAutowireMethodName($class));
        $classMethod->flags      = Modifiers::PUBLIC;
        $classMethod->params     = [$param];
        $classMethod->returnType = new Identifier('void');
        $classMethod->stmts      = [$assignment];

        // Symfony calls every #[Required] method on service instantiation.
        $classMethod->attrGroups = [
            new AttributeGroup([new Attribute(new FullyQualified('Symfony\Contracts\Service\Attribute\Required'))]),
        ];

        return $classMethod;
    }

    private function createProperty(string $repositoryClass, string $propertyName): Property
    {
        $property       = new Property(Modifiers::PRIVATE, [new PropertyItem($propertyName)]);
        $property->type = new FullyQualified($repositoryClass);

        return $property;
    }

    /**
     * The name is fixed by AutowireMethodNameMustMatchClassRule: "autowire" + the short class name.
     */
    private function resolveAutowireMethodName(Class_ $class): string
    {
        return 'autowire'.$class->name?->toString();
    }

    /**
     * Mautic\PageBundle\Entity\HitRepository -> hitRepository.
     */
    private function resolvePropertyName(string $repositoryClass): string
    {
        $shortName = (string) strrchr('\\'.$repositoryClass, '\\');

        return lcfirst(ltrim($shortName, '\\'));
    }

    /**
     * Swaps the matched call for its replacement, in place, wherever it sits in the class.
     */
    private function replaceNode(Class_ $class, MethodCall $oldNode, Node $newNode): void
    {
        $this->traverseNodesWithCallable($class, static fn (Node $node): ?Node => $node === $oldNode ? $newNode : null);
    }
}
