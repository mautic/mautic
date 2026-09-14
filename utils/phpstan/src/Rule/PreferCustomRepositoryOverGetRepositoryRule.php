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
 * getRepository(Entity::class) on an entity that declares a custom repository returns only a generic type,
 * hiding the concrete repository from the reader and static analysis. Inject that repository as a typed
 * dependency and use it directly.
 *
 * The custom repository class is read from the entity's loadMetadata() setCustomRepositoryClass() call.
 *
 * @implements Rule<MethodCall>
 */
final class PreferCustomRepositoryOverGetRepositoryRule implements Rule
{
    private const string GET_REPOSITORY_METHOD = 'getRepository';

    private const string SET_CUSTOM_REPOSITORY_METHOD = 'setCustomRepositoryClass';

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
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if (self::GET_REPOSITORY_METHOD !== $node->name->toString()) {
            return [];
        }

        // tests may fetch entities directly through getRepository() for convenience
        if (1 === preg_match('#/Tests?/#', $scope->getFile())) {
            return [];
        }

        // only a Doctrine registry or entity manager exposes this getRepository()
        $callerType = $scope->getType($node->var);
        $isDoctrineCaller = new ObjectType(ManagerRegistry::class)->isSuperTypeOf($callerType)->yes()
            || new ObjectType(ObjectManager::class)->isSuperTypeOf($callerType)->yes();
        if (!$isDoctrineCaller) {
            return [];
        }

        $firstArg = $node->getArgs()[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return [];
        }

        $entityClass = $this->resolveEntityClass($firstArg->value, $scope);
        if (null === $entityClass) {
            return [];
        }

        $customRepositoryClass = $this->resolveCustomRepositoryClass($entityClass);
        if (null === $customRepositoryClass) {
            return [];
        }

        $ruleError = RuleErrorBuilder::message(sprintf(
            'Entity "%s" declares the custom repository "%s". Inject and use that repository as a typed dependency instead of getRepository(), to make the dependency and its type explicit.',
            $entityClass,
            $customRepositoryClass
        ))
            ->identifier('mautic.preferCustomRepositoryOverGetRepository')
            ->build();

        return [$ruleError];
    }

    private function resolveEntityClass(Node\Expr $expr, Scope $scope): ?string
    {
        // only an exact entity constant, e.g. Lead::class
        if (!$expr instanceof ClassConstFetch) {
            return null;
        }

        $constantStrings = $scope->getType($expr)->getConstantStrings();
        if (1 !== count($constantStrings)) {
            return null;
        }

        return $constantStrings[0]->getValue();
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
}
