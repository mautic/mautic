<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * A repository must not be fetched by an entity class constant, e.g. getRepository(Lead::class).
 *
 * The returned repository has a generic type only, so the concrete repository methods are hidden from both the
 * reader and static analysis. Inject the repository as a typed dependency instead.
 *
 * The call is reported when the caller is an entity manager, when it happens inside a repository, or when a
 * manager registry fetches an entity that declares a custom repository via loadMetadata().
 *
 * Tests are skipped, as fetching an entity by its repository is a legit shortcut there.
 *
 * @implements Rule<MethodCall>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoGetRepositoryWithEntityRuleTest
 */
final class NoGetRepositoryWithEntityRule implements Rule
{
    private const string GET_REPOSITORY_METHOD = 'getRepository';

    private const string SET_CUSTOM_REPOSITORY_METHOD = 'setCustomRepositoryClass';

    private const string REPOSITORY_SUFFIX = 'Repository.php';

    private ?Parser $parser = null;

    /**
     * @var array<string, string|null>
     */
    private array $customRepositoryCache = [];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // inside a trait the scope file is the class using it, so the very same call would be reported
        // once per using class, in a file that does not contain it
        if ($scope->isInTrait()) {
            return [];
        }

        if ($this->isTestFile($scope->getFile())) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if (self::GET_REPOSITORY_METHOD !== $node->name->toString()) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return [];
        }

        $entityClass = $this->resolveEntityClass($firstArg->value);
        if (null === $entityClass) {
            return [];
        }

        if (!$this->shouldReport($node, $scope, $entityClass)) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Do not fetch the "%s" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.',
            $entityClass
        ))
            ->identifier('mautic.noGetRepository')
            ->build();

        return [$ruleError];
    }

    private function shouldReport(MethodCall $methodCall, Scope $scope, string $entityClass): bool
    {
        $callerType = $scope->getType($methodCall->var);

        // an entity manager, a property, a variable or any other expression alike
        if (new ObjectType(ObjectManager::class)->isSuperTypeOf($callerType)->yes()) {
            return true;
        }

        // a repository already knows its own entity, so reaching for another one hides a cross-repository dependency
        if (str_ends_with($scope->getFile(), self::REPOSITORY_SUFFIX)) {
            return true;
        }

        // a manager registry fetching an entity that declares its own repository class
        return new ObjectType(ManagerRegistry::class)->isSuperTypeOf($callerType)->yes()
            && null !== $this->resolveCustomRepositoryClass($entityClass);
    }

    private function resolveEntityClass(Node\Expr $expr): ?string
    {
        // only an exact entity constant, e.g. Lead::class
        if (!$expr instanceof ClassConstFetch) {
            return null;
        }

        if (!$expr->class instanceof Name) {
            return null;
        }

        if (!$expr->name instanceof Node\Identifier || 'class' !== $expr->name->toString()) {
            return null;
        }

        return $expr->class->toString();
    }

    private function resolveCustomRepositoryClass(string $entityClass): ?string
    {
        if (array_key_exists($entityClass, $this->customRepositoryCache)) {
            return $this->customRepositoryCache[$entityClass];
        }

        return $this->customRepositoryCache[$entityClass] = $this->readCustomRepositoryClass($entityClass);
    }

    private function readCustomRepositoryClass(string $entityClass): ?string
    {
        if (!$this->reflectionProvider->hasClass($entityClass)) {
            return null;
        }

        $fileName = $this->reflectionProvider->getClass($entityClass)->getFileName();
        if (null === $fileName || !is_file($fileName)) {
            return null;
        }

        $statements = $this->getParser()->parse((string) file_get_contents($fileName));
        if (null === $statements) {
            return null;
        }

        // resolve aliased names so PermissionRepository::class becomes the fully qualified name
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $statements = $traverser->traverse($statements);

        $setCustomRepositoryCall = new NodeFinder()->findFirst(
            $statements,
            static fn (Node $subNode): bool => $subNode instanceof MethodCall
                && $subNode->name instanceof Node\Identifier
                && self::SET_CUSTOM_REPOSITORY_METHOD === $subNode->name->toString()
        );

        if (!$setCustomRepositoryCall instanceof MethodCall) {
            return null;
        }

        $repositoryArg = $setCustomRepositoryCall->getArgs()[0] ?? null;
        if (!$repositoryArg instanceof Node\Arg || !$repositoryArg->value instanceof ClassConstFetch) {
            return null;
        }

        if (!$repositoryArg->value->class instanceof Name) {
            return null;
        }

        return $repositoryArg->value->class->toString();
    }

    private function getParser(): Parser
    {
        return $this->parser ??= new ParserFactory()->createForNewestSupportedVersion();
    }

    private function isTestFile(string $filePath): bool
    {
        return 1 === preg_match('#/Tests?/#', $filePath)
            || str_ends_with($filePath, 'Test.php')
            || str_ends_with($filePath, 'TestCase.php');
    }
}
