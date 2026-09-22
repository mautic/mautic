<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\DTO;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Security\OIDC\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testGetters(): void
    {
        $role       = $this->createStub(Role::class);
        $parameters = new Settings(true, true, true, $role);

        $this->assertTrue($parameters->isEnabled());
        $this->assertTrue($parameters->isRequired());
        $this->assertTrue($parameters->isUserRegistrationAllowed());
        $this->assertSame($role, $parameters->getRegisteredUserRole());
    }
}
