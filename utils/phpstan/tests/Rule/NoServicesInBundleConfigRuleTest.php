<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoServicesInBundleConfigRule;

/**
 * @extends RuleTestCase<NoServicesInBundleConfigRule>
 */
final class NoServicesInBundleConfigRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoServicesInBundleConfigRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/BundleWithServices/config.php'], [
            [
                'Config file must not define services. Register the "others" group in the autowired Config/services.php instead.',
                8,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/BundleWithoutServices/config.php'], []);

        // menus are services too, they belong in the autowired Config/services.php as well
        $this->analyse([__DIR__.'/Fixture/BundleWithMenus/config.php'], [
            [
                'Config file must not define services. Register the "menus" group in the autowired Config/services.php instead.',
                8,
            ],
            [
                'Config file must not define services. Register the "others" group in the autowired Config/services.php instead.',
                13,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/BundleWithMenusOnly/config.php'], [
            [
                'Config file must not define services. Register the "menus" group in the autowired Config/services.php instead.',
                8,
            ],
        ]);

        // a config of a Tests directory is a fixture, not a bundle config
        $this->analyse([__DIR__.'/Fixture/BundleWithServices/Tests/config.php'], []);
    }
}
