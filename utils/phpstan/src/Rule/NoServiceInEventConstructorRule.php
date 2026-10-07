<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An event carries data, not services.
 *
 * This flags a service that an event only stashes to hand to its listeners through a getter - the
 * service belongs in the listener, not on the event. A service the event actually uses in its own
 * methods is left alone, as translating a label or checking a permission is the event's own work.
 *
 * @implements Rule<Class_>
 */
final readonly class NoServiceInEventConstructorRule implements Rule
{
    private const string CONSTRUCTOR = '__construct';

    /**
     * @var string[]
     */
    private const array SERVICE_TYPES = [
        \Doctrine\ORM\EntityManagerInterface::class,
        \Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface::class,
        \Mautic\ChannelBundle\Helper\ChannelListHelper::class,
        \Mautic\CoreBundle\Helper\BundleHelper::class,
        \Mautic\CoreBundle\Menu\MenuHelper::class,
        \Mautic\CoreBundle\Security\Permissions\CorePermissions::class,
        \Mautic\CoreBundle\Twig\Helper\AssetsHelper::class,
        \Mautic\ReportBundle\Helper\ReportHelper::class,
        \Symfony\Component\Security\Core\User\UserProviderInterface::class,
        \Symfony\Contracts\Translation\TranslatorInterface::class,
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->namespacedName instanceof Name) {
            return [];
        }

        $className = $node->namespacedName->toString();
        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if (!$classReflection->is(Event::class)) {
            return [];
        }

        $constructor = $node->getMethod(self::CONSTRUCTOR);
        if (!$constructor instanceof ClassMethod) {
            return [];
        }

        $ruleErrors = [];

        foreach ($constructor->params as $param) {
            if (!$param->type instanceof Name) {
                continue;
            }

            $paramType = $scope->resolveName($param->type);
            if (!$this->isService($paramType)) {
                continue;
            }

            $propertyName = $this->propertyName($param, $constructor);
            if (null === $propertyName) {
                continue;
            }

            if ($this->isUsedInternally($node, $propertyName)) {
                continue;
            }

            $parameterName = $param->var instanceof Variable && is_string($param->var->name)
                ? '$'.$param->var->name
                : '';

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Service "%s" of type "%s" is only carried by this event and handed to its listeners. Inject it in the listener instead of passing it through the event.',
                $parameterName,
                $paramType,
            ))
                ->identifier('mautic.noServiceInEventConstructor')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function propertyName(Param $param, ClassMethod $constructor): ?string
    {
        if (!$param->var instanceof Variable || !is_string($param->var->name)) {
            return null;
        }

        // promoted property: the parameter name is the property name
        if (0 !== $param->flags) {
            return $param->var->name;
        }

        // plain parameter assigned to a property in the constructor body: $this->foo = $param;
        foreach ($constructor->stmts ?? [] as $statement) {
            if (!$statement instanceof Expression || !$statement->expr instanceof Node\Expr\Assign) {
                continue;
            }

            $assign = $statement->expr;
            if (!$assign->var instanceof PropertyFetch || !$assign->var->name instanceof Identifier) {
                continue;
            }

            if ($assign->expr instanceof Variable && $assign->expr->name === $param->var->name) {
                return $assign->var->name->toString();
            }
        }

        return null;
    }

    /**
     * The property is "used internally" when it appears anywhere other than as the whole return
     * value of a getter - i.e. the event itself calls into the service.
     */
    private function isUsedInternally(Class_ $class, string $propertyName): bool
    {
        $nodeFinder = new NodeFinder();

        $propertyFetches = $nodeFinder->find($class->getMethods(), fn (Node $node): bool => $node instanceof PropertyFetch
            && $node->var instanceof Variable
            && 'this' === $node->var->name
            && $node->name instanceof Identifier
            && $node->name->toString() === $propertyName);

        $returnedFetches = $nodeFinder->find($class->getMethods(), fn (Node $node): bool => $node instanceof Return_
            && $node->expr instanceof PropertyFetch
            && $node->expr->var instanceof Variable
            && 'this' === $node->expr->var->name
            && $node->expr->name instanceof Identifier
            && $node->expr->name->toString() === $propertyName);

        return count($propertyFetches) > count($returnedFetches);
    }

    private function isService(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return array_any(self::SERVICE_TYPES, static fn (string $serviceType): bool => $classReflection->is($serviceType));
    }
}
