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
                'Config file must not define the "services" key. Register the services in the autowired Config/services.php instead.',
                7,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/BundleWithoutServices/config.php'], []);

        // a menu is no service of its own, ServicePass builds it out of the KnpMenu builder
        $this->analyse([__DIR__.'/Fixture/BundleWithMenus/config.php'], []);
    }
}
