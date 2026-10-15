<?php

declare(strict_types=1);

namespace Utils\PHPStan\Rule;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\OAuthServerBundle\Controller\AuthorizeController;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Translation\Translator;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;

/**
 * A shared service must be injected, not passed around as a method parameter.
 *
 * Passing e.g. a FormFactoryInterface into "createForm($entity, FormFactoryInterface $formFactory)" hides the real
 * dependency of the class in every call site, and forces each caller to have the service itself. The class that uses
 * the service should ask for it once - in the constructor, or in an autowire*() method for classes that cannot use a
 * constructor (controllers).
 *
 * Only the injection entry points are allowed to take a service: "__construct()" and "autowire*()"/#[Required] methods.
 *
 * @implements Rule<ClassMethod>
 *
 * @see \Utils\PHPStan\Tests\Rule\NoServiceInMethodParameterRuleTest
 */
final readonly class NoServiceInMethodParameterRule implements Rule
{
    /**
     * Services that are always container-provided singletons. Subtypes are matched too, so RouterInterface covers
     * UrlGeneratorInterface implementations as well. Value objects that Symfony passes by design - Request, Response,
     * events, form builders, console input/output - are deliberately absent.
     *
     * @var string[]
     */
    private const array SERVICE_TYPES = [
        EntityManagerInterface::class,
        ManagerRegistry::class,
        CacheItemPoolInterface::class,
        LoggerInterface::class,
        Filesystem::class,
        FormFactoryInterface::class,
        RequestStack::class,
        KernelInterface::class,
        MailerInterface::class,
        MessageBusInterface::class,
        UserPasswordHasherInterface::class,
        UrlGeneratorInterface::class,
        TokenStorageInterface::class,
        AuthorizationCheckerInterface::class,
        CsrfTokenManagerInterface::class,
        SerializerInterface::class,
        ValidatorInterface::class,
        CacheInterface::class,
        EventDispatcherInterface::class,
        HttpClientInterface::class,
        TranslatorInterface::class,
        Environment::class,
    ];

    /**
     * The container builder is only ever handed to a DI extension or a compiler pass, both of which get it as a
     * method parameter by Symfony design - there is nothing to inject there. The Mautic translator is a different
     * kind of translator than the contract one, and is passed around on purpose.
     *
     * @var string[]
     */
    private const array SKIPPED_TYPES = [
        ContainerBuilder::class,
        Translator::class,
    ];

    /**
     * Methods whose signature is dictated by a shared parent class, so a single child cannot drop the service
     * parameter on its own.
     *
     * @var array<string, string>
     */
    private const array SKIPPED_PARENT_CLASS_METHODS = [FormModel::class => 'createform'];

    /**
     * A test case has no container to inject from - it fetches services itself and hands them to its own data
     * builders and helper methods.
     */
    private const string TEST_CASE_CLASS = TestCase::class;

    /**
     * An event or an entity is created by the code that uses it, never by the container, so a service it needs can
     * only arrive through a method parameter. A Twig extension gets the environment handed to its functions by Twig
     * itself, and the FOS authorize controller is no real controller either - it is instantiated by the bundle, so
     * its methods are plain calls.
     *
     * @var string[]
     */
    private const array SKIPPED_CLASS_TYPES = [
        Event::class,
        'Symfony\\Component\\EventDispatcher\\Event',
        CommonEntity::class,
        AbstractExtension::class,
        AuthorizeController::class,
    ];

    /**
     * A controller action gets its services from the Symfony argument resolver, which is a container injection of
     * its own - there is no call site that would have to carry the service.
     */
    private const string CONTROLLER_SUFFIX = 'Controller';

    private const string ACTION_SUFFIX = 'action';

    /**
     * A create*() method is a factory: it builds the object for its caller, so the service it builds with belongs to
     * the caller, not to the factory.
     */
    private const string CREATE_PREFIX = 'create';

    private const string AUTOWIRE_PREFIX = 'autowire';

    private const string REQUIRED_ATTRIBUTE = Required::class;

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
        if ($this->isInjectionMethod($node)) {
            return [];
        }

        if ($this->isInheritedParentClassMethod($node, $scope)) {
            return [];
        }

        // a trait method is reported once per class using it, and the trait has no constructor of its own to inject in
        if ($scope->isInTrait()) {
            return [];
        }

        if ($this->isInTestCase($scope)) {
            return [];
        }

        if ($this->isInSkippedClassType($scope)) {
            return [];
        }

        if ($this->isControllerAction($node, $scope)) {
            return [];
        }

        if (str_starts_with($node->name->toLowerString(), self::CREATE_PREFIX)) {
            return [];
        }

        // a static method has no instance to inject into, so its caller has to hand the service over
        if ($node->isStatic()) {
            return [];
        }

        $methodName = $node->name->toString();

        $ruleErrors = [];

        foreach ($node->params as $param) {
            if (!$param->type instanceof Name) {
                continue;
            }

            $paramType = $scope->resolveName($param->type);
            if (!$this->isServiceType($paramType)) {
                continue;
            }

            $parameterName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
                ? '$'.$param->var->name
                : '';

            $ruleErrors[] = RuleErrorBuilder::message(sprintf(
                'Service "%s" of type "%s" is passed to method "%s()". Inject it in the constructor or an autowire*() method instead.',
                $parameterName,
                $paramType,
                $methodName
            ))
                ->identifier('mautic.noServiceInMethodParameter')
                ->line($param->getStartLine())
                ->build();
        }

        return $ruleErrors;
    }

    private function isInjectionMethod(ClassMethod $classMethod): bool
    {
        $methodName = $classMethod->name->toLowerString();

        if ('__construct' === $methodName) {
            return true;
        }

        if (str_starts_with($methodName, self::AUTOWIRE_PREFIX)) {
            return true;
        }

        foreach ($classMethod->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (self::REQUIRED_ATTRIBUTE === $attr->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The parent class itself is skipped too, as it declares the signature every child has to keep.
     */
    private function isInheritedParentClassMethod(ClassMethod $classMethod, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        $methodName = $classMethod->name->toLowerString();

        return array_any(self::SKIPPED_PARENT_CLASS_METHODS, fn (string $skippedMethodName, $parentClass): bool => $methodName === $skippedMethodName && $classReflection->is($parentClass));
    }

    private function isControllerAction(ClassMethod $classMethod, Scope $scope): bool
    {
        if (!str_ends_with($classMethod->name->toLowerString(), self::ACTION_SUFFIX)) {
            return false;
        }

        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        return str_ends_with($classReflection->getName(), self::CONTROLLER_SUFFIX);
    }

    private function isInSkippedClassType(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        return array_any(self::SKIPPED_CLASS_TYPES, fn (string $skippedClassType): bool => $classReflection->is($skippedClassType));
    }

    private function isInTestCase(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();
        if (!$classReflection instanceof \PHPStan\Reflection\ClassReflection) {
            return false;
        }

        return $classReflection->is(self::TEST_CASE_CLASS);
    }

    private function isServiceType(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        foreach (self::SKIPPED_TYPES as $skippedType) {
            if ($classReflection->is($skippedType)) {
                return false;
            }
        }

        return array_any(self::SERVICE_TYPES, fn (string $serviceType): bool => $classReflection->is($serviceType));
    }
}
