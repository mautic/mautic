<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\PreferInterfaceInConstructorRule;

/**
 * @extends RuleTestCase<PreferInterfaceInConstructorRule>
 */
final class PreferInterfaceInConstructorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferInterfaceInConstructorRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConcreteClassConstructor.php'], [
            [
                'Constructor dependency "$router" is typed as concrete "Symfony\Component\Routing\Router". Use the "Symfony\Component\Routing\RouterInterface" interface instead.',
                13,
            ],
            [
                'Constructor dependency "$entityManager" is typed as concrete "Doctrine\ORM\EntityManager". Use the "Doctrine\ORM\EntityManagerInterface" interface instead.',
                14,
            ],
        ]);
    }

    public function testSkipInterfaceType(): void
    {
        $this->analyse([__DIR__.'/Fixture/InterfaceConstructor.php'], []);
    }

    public function testSkipClassWithoutSameNamedInterface(): void
    {
        $this->analyse([__DIR__.'/Fixture/ClassWithoutInterfaceConstructor.php'], []);
    }

    public function testSkipSession(): void
    {
        $this->analyse([__DIR__.'/Fixture/SessionConstructor.php'], []);
    }

    public function testSkipClassOutsideSymfonyAndDoctrine(): void
    {
        $this->analyse([__DIR__.'/Fixture/NonVendorConstructor.php'], []);
    }

    public function testSkipGuzzleClient(): void
    {
        $this->analyse([__DIR__.'/Fixture/GuzzleClientConstructor.php'], []);
    }

    public function testSkipConfigLoader(): void
    {
        $this->analyse([__DIR__.'/Fixture/ConfigLoaderConstructor.php'], []);
    }
}
