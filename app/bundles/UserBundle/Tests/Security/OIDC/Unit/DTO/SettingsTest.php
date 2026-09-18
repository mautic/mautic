<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\DTO;

use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Security\OIDC\DTO\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testGetters(): void
    {
        $role       = $this->createMock(Role::class);
        $parameters = new Settings(true, true, true, $role);

        self::assertTrue($parameters->isEnabled());
        self::assertTrue($parameters->isRequired());
        self::assertTrue($parameters->isUserRegistrationAllowed());
        self::assertSame($role, $parameters->getRegisteredUserRole());
    }
}
