<?php

declare(strict_types=1);

namespace Utils\PHPStan;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Translation\Translator;
use PHPStan\Reflection\ReflectionProvider;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Tells whether a class is a shared container-provided service, used by the rules that forbid passing a service
 * as a method parameter or promoting it to a native type.
 */
final readonly class ServiceTypeDetector
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

    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function isServiceType(string $className): bool
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
