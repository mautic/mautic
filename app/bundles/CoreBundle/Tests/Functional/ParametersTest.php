<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class ParametersTest extends AbstractMauticTestCase
{
    public function testRememberMeParameterUsesIntProcessor(): void
    {
        $this->assertSame(7_776_000, self::getContainer()->getParameter('mautic.rememberme_lifetime'));
    }
}
