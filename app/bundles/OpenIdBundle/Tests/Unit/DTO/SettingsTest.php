<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Unit\DTO;

use Mautic\OpenIdBundle\DTO\Settings;
use Mautic\UserBundle\Entity\Role;
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
