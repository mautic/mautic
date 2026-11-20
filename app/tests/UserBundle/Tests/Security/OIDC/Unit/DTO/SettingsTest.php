<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\DTO;

use Mautic\UserBundle\Security\OIDC\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testGetters(): void
    {
        $settings = new Settings(true, true, true, 5);

        $this->assertTrue($settings->isEnabled());
        $this->assertTrue($settings->isRequired());
        $this->assertTrue($settings->isUserRegistrationAllowed());
        $this->assertSame(5, $settings->getRegisteredUserRoleId());
    }
}
