<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Twig\Extension\ExtensionInterface;

/**
 * Reports the tags of Config/services.php that autoconfigure() adds by itself,
 * e.g. $services->set(OAuthCallbackValidator::class)->tag('validator.constraint_validator').
 *
 * Every bundle config opens with $services->defaults()->autoconfigure(), so a service implementing
 * ConstraintValidatorInterface is tagged "validator.constraint_validator" either way - the tag repeats
 * what the interface already says.
 *
 * Only a tag with no attributes of its own is reported. An attribute says more than autoconfigure() does
 * and so keeps the tag, e.g. ->tag('twig.extension', ['priority' => 100]) or a validator alias:
 *
 *     $services->set(FileExtensionConstraintValidator::class)
 *         ->tag('validator.constraint_validator', ['alias' => 'file_extension_constraint_validator']);
 *
 * A file with no autoconfigure() in its defaults is left alone, there the tag is the only thing that
 * registers the service.
 *
 * @implements Rule<FileNode>
 */
final class NoAutoconfiguredServiceTagRule implements Rule
{
    /**
     * @var string
     */
    private const SERVICES_FILE_NAME = 'services.php';

    /**
     * @var string
     */
    private const SERVICES_VARIABLE_NAME = 'services';

    /**
     * The tags Symfony adds on its own to a service of the given type.
     *
     * @see \Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension::load()
     * @see \Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension::load()
     *
     * @var array<string, string>
     */
    private const AUTOCONFIGURED_TAGS = [
        'console.command'                => Command::class,
        'form.type'                      => FormTypeInterface::class,
        'kernel.event_subscriber'        => EventSubscriberInterface::class,
        'security.voter'                 => VoterInterface::class,
        'twig.extension'                 => ExtensionInterface::class,
        'validator.constraint_validator' => ConstraintValidatorInterface::class,
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param FileNode $node
     *
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (self::SERVICES_FILE_NAME !== basename($scope->getFile())) {
            return [];
        }

        $nodeFinder = new NodeFinder();

        /** @var MethodCall[] $methodCalls */
        $methodCalls = $nodeFinder->findInstanceOf($node->getNodes(), MethodCall::class);

        if (!$this->hasAutoconfigureCall($methodCalls)) {
            return [];
        }

        $ruleErrors = [];

        foreach ($methodCalls as $methodCall) {
            $tagName = $this->matchTagNameWithoutAttributes($methodCall);
            if (null === $tagName) {
                continue;
            }

            $autoconfiguredType = self::AUTOCONFIGURED_TAGS[$tagName] ?? null;
            if (null === $autoconfiguredType) {
                continue;
            }

            $className = $this->resolveTaggedClassName($methodCall);
            if (null === $className) {
                continue;
            }

            if (!$this->isSubclassOf($className, $autoconfiguredType)) {
                continue;
            }

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Tag "%s" is added by autoconfigure() on its own, as "%s" is a %s - remove the ->tag() call.',
                $tagName,
                $className,
                $autoconfiguredType
            ))
                ->identifier('mautic.noAutoconfiguredServiceTag')
                // the name, not the call - a chained call starts on the line of the $services->set() above it
                ->line($methodCall->name->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    /**
     * @param MethodCall[] $methodCalls
     */
    private function hasAutoconfigureCall(array $methodCalls): bool
    {
        foreach ($methodCalls as $methodCall) {
            if ($methodCall->name instanceof Identifier && 'autoconfigure' === $methodCall->name->toString()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string|null the tag name of a ->tag() call that adds no attributes of its own
     */
    private function matchTagNameWithoutAttributes(MethodCall $methodCall): ?string
    {
        if (!$methodCall->name instanceof Identifier || 'tag' !== $methodCall->name->toString()) {
            return null;
        }

        $args = $methodCall->getArgs();
        if (1 !== count($args)) {
            return null;
        }

        return $args[0]->value instanceof String_ ? $args[0]->value->value : null;
    }

    /**
     * Walks the call chain down to the $services->set() or $services->get() call the tag belongs to.
     */
    private function resolveTaggedClassName(MethodCall $methodCall): ?string
    {
        $currentCall = $methodCall->var;

        while ($currentCall instanceof MethodCall) {
            if ($currentCall->var instanceof Variable && self::SERVICES_VARIABLE_NAME === $currentCall->var->name) {
                if (!$currentCall->name instanceof Identifier || !in_array($currentCall->name->toString(), ['set', 'get'], true)) {
                    return null;
                }

                $args = $currentCall->getArgs();

                return [] === $args ? null : $this->matchClassName($args[0]->value);
            }

            $currentCall = $currentCall->var;
        }

        return null;
    }

    private function matchClassName(Node $classValue): ?string
    {
        if (!$classValue instanceof ClassConstFetch || !$classValue->class instanceof Name) {
            return null;
        }

        if (!$classValue->name instanceof Identifier || 'class' !== $classValue->name->toLowerString()) {
            return null;
        }

        return $classValue->class->toString();
    }

    private function isSubclassOf(string $className, string $parentClassName): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        return $this->reflectionProvider->getClass($className)->is($parentClassName);
    }
}
