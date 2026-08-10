<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;
use Twig\Environment;
use Twig\Extension\AttributeExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;

/**
 * Every Twig filter, function and test declared by Mautic - either by the getFilters()/getFunctions()/getTests()
 * methods or by the #[AsTwigFilter]/#[AsTwigFunction]/#[AsTwigTest] attributes - must end up registered
 * in the Twig environment.
 */
final class TwigCallableRegistrationTest extends TestCase
{
    /**
     * @var array<class-string, string>
     */
    private const ATTRIBUTE_TO_KIND = [
        AsTwigFilter::class   => 'filter',
        AsTwigFunction::class => 'function',
        AsTwigTest::class     => 'test',
    ];

    private static Environment $twigEnvironment;

    public static function setUpBeforeClass(): void
    {
        $twigEnvironment = new Environment(new ArrayLoader());

        foreach (self::resolveTwigClasses() as $class => $usesAttributes) {
            // mirrors Symfony\Bundle\TwigBundle\DependencyInjection\Compiler\AttributeExtensionPass
            if ($usesAttributes) {
                $twigEnvironment->addExtension(new AttributeExtension($class));
                continue;
            }

            $twigEnvironment->addExtension((new \ReflectionClass($class))->newInstanceWithoutConstructor());
        }

        self::$twigEnvironment = $twigEnvironment;
    }

    #[DataProvider('provideDeclaredTwigCallables')]
    public function testDeclaredTwigCallableIsRegistered(string $kind, string $name, string $class): void
    {
        if ('filter' === $kind) {
            $registeredCallable = self::$twigEnvironment->getFilter($name);
        } elseif ('function' === $kind) {
            $registeredCallable = self::$twigEnvironment->getFunction($name);
        } else {
            $registeredCallable = self::$twigEnvironment->getTest($name);
        }

        $this->assertNotNull(
            $registeredCallable,
            sprintf('Twig %s "%s" declared in "%s" is not registered in the Twig environment.', $kind, $name, $class)
        );
    }

    /**
     * Twig keeps the last registered callable of the same name, so a duplicated name silently drops the other one.
     */
    public function testDeclaredTwigCallableNamesAreUnique(): void
    {
        $classesByCallable = [];

        foreach (self::provideDeclaredTwigCallables() as [$kind, $name, $class]) {
            $callable = $kind.' "'.$name.'"';

            $this->assertArrayNotHasKey(
                $callable,
                $classesByCallable,
                sprintf('Twig %s is declared in both "%s" and "%s".', $callable, $classesByCallable[$callable] ?? '', $class)
            );

            $classesByCallable[$callable] = $class;
        }
    }

    /**
     * @see \Symfony\Bundle\TwigBundle\DependencyInjection\Compiler\AttributeExtensionPass::autoconfigureFromAttribute()
     */
    public function testTwigAttributesAreUsableByTwig(): void
    {
        foreach (self::resolveTwigClasses() as $class => $usesAttributes) {
            if (!$usesAttributes) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);

            $this->assertFalse(
                $reflectionClass->implementsInterface(ExtensionInterface::class),
                sprintf('Class "%s" must not implement "%s" and use Twig attributes at the same time.', $class, ExtensionInterface::class)
            );

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                foreach (array_keys(self::ATTRIBUTE_TO_KIND) as $attributeClass) {
                    if ([] === $reflectionMethod->getAttributes($attributeClass)) {
                        continue;
                    }

                    $this->assertTrue(
                        $reflectionMethod->isPublic(),
                        sprintf('Method "%s::%s()" declares a Twig callable, so it must be public.', $class, $reflectionMethod->getName())
                    );
                }
            }
        }
    }

    /**
     * @return iterable<string, array{string, string, class-string}>
     */
    public static function provideDeclaredTwigCallables(): iterable
    {
        foreach (self::resolveTwigClasses() as $class => $usesAttributes) {
            $reflectionClass = new \ReflectionClass($class);

            if ($usesAttributes) {
                foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                    foreach (self::ATTRIBUTE_TO_KIND as $attributeClass => $kind) {
                        foreach ($reflectionMethod->getAttributes($attributeClass) as $reflectionAttribute) {
                            $name = $reflectionAttribute->newInstance()->name;

                            yield $kind.' '.$name.' ('.$class.')' => [$kind, $name, $class];
                        }
                    }
                }

                continue;
            }

            /** @var ExtensionInterface $extension */
            $extension = $reflectionClass->newInstanceWithoutConstructor();

            $declaredCallables = [
                'filter'   => $extension->getFilters(),
                'function' => $extension->getFunctions(),
                'test'     => $extension->getTests(),
            ];

            foreach ($declaredCallables as $kind => $twigCallables) {
                foreach ($twigCallables as $twigCallable) {
                    $name = $twigCallable->getName();

                    yield $kind.' '.$name.' ('.$class.')' => [$kind, $name, $class];
                }
            }
        }
    }

    /**
     * @return array<class-string, bool> class name => declares its Twig callables by attributes
     */
    private static function resolveTwigClasses(): array
    {
        $projectDirectory = dirname(__DIR__, 5);

        $finder = (new Finder())
            ->files()
            ->in([$projectDirectory.'/app/bundles', $projectDirectory.'/plugins'])
            ->name('*.php')
            ->notPath('Tests')
            ->contains('/AsTwigFilter|AsTwigFunction|AsTwigTest|ExtensionInterface|AbstractExtension/');

        $twigClasses = [];

        foreach ($finder as $fileInfo) {
            $contents = $fileInfo->getContents();

            if (!preg_match('#^namespace\s+(?<namespace>.+?);#m', $contents, $namespaceMatch)) {
                continue;
            }

            if (!preg_match('#^(?:final\s+|readonly\s+|abstract\s+)*class\s+(?<class>\w+)#m', $contents, $classMatch)) {
                continue;
            }

            $class = $namespaceMatch['namespace'].'\\'.$classMatch['class'];

            if (!class_exists($class)) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($class);

            if ($reflectionClass->isAbstract()) {
                continue;
            }

            $usesAttributes = self::usesTwigAttributes($reflectionClass);

            if (!$usesAttributes && !$reflectionClass->implementsInterface(ExtensionInterface::class)) {
                continue;
            }

            $twigClasses[$class] = $usesAttributes;
        }

        return $twigClasses;
    }

    /**
     * @param \ReflectionClass<object> $reflectionClass
     */
    private static function usesTwigAttributes(\ReflectionClass $reflectionClass): bool
    {
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            foreach (array_keys(self::ATTRIBUTE_TO_KIND) as $attributeClass) {
                if ([] !== $reflectionMethod->getAttributes($attributeClass)) {
                    return true;
                }
            }
        }

        return false;
    }
}
