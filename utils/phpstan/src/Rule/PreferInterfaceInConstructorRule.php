<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A constructor dependency must be autowired by its interface, not by the concrete class.
 *
 * Typing "Router $router" locks the service to one implementation and blocks decoration, while
 * "RouterInterface $router" describes what the class actually needs. The interface is discovered by
 * convention: a param typed "X" is reported when "XInterface" exists and "X" implements it. Only
 * Symfony and Doctrine classes are checked - Mautic's own classes are left alone.
 *
 * @implements Rule<ClassMethod>
 */
final readonly class PreferInterfaceInConstructorRule implements Rule
{
    /**
     * @var string[]
     */
    /**
     * Only 3rd-party contracts are enforced - Mautic's own classes are free to be typed directly.
     *
     * @var string[]
     */
    private const array HANDLED_NAMESPACE_PREFIXES = ['Symfony\\', 'Doctrine\\'];

    /**
     * @var string[]
     */
    private const array SKIPPED_INTERFACES = [
        // the concrete Session is the only way to reach getFlashBag()
        \Symfony\Component\HttpFoundation\Session\SessionInterface::class,
        // route loading relies on the concrete Loader
        \Symfony\Component\Config\Loader\LoaderInterface::class,
        // only the concrete TransportFactory is aliased as a service, see MessengerBundle/Config/services.php
        \Symfony\Component\Messenger\Transport\TransportFactoryInterface::class,
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ('__construct' !== $node->name->toLowerString()) {
            return [];
        }

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->type instanceof Name) {
                continue;
            }

            $className     = $scope->resolveName($param->type);
            $interfaceName = $this->matchImplementedSameNamedInterface($className);
            if (null === $interfaceName) {
                continue;
            }

            $parameterName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? '$'.$param->var->name
                : '';

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Constructor dependency "%s" is typed as concrete "%s". Use the "%s" interface instead.',
                $parameterName,
                $className,
                $interfaceName
            ))
                ->identifier('mautic.preferInterfaceInConstructor')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * Returns the "<class>Interface" name when the class implements it, null otherwise.
     */
    private function matchImplementedSameNamedInterface(string $className): ?string
    {
        if (!$this->isHandledNamespace($className)) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);
        if ($classReflection->isInterface()) {
            return null;
        }

        $interfaceName = $className.'Interface';
        if (in_array($interfaceName, self::SKIPPED_INTERFACES, true)) {
            return null;
        }

        if (!$this->reflectionProvider->hasClass($interfaceName)) {
            return null;
        }

        if (!$this->reflectionProvider->getClass($interfaceName)->isInterface()) {
            return null;
        }

        if (!$classReflection->implementsInterface($interfaceName)) {
            return null;
        }

        return $interfaceName;
    }

    private function isHandledNamespace(string $className): bool
    {
        return array_any(self::HANDLED_NAMESPACE_PREFIXES, fn (string $handledNamespacePrefix): bool => str_starts_with($className, $handledNamespacePrefix));
    }
}
