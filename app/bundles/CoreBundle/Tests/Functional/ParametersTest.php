<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;

final class ParametersTest extends AbstractMauticTestCase
{
    public function testRememberMeParameterUsesIntProcessor(): void
    {
        $this->assertSame(7_776_000, static::getContainer()->getParameter('mautic.rememberme_lifetime'));
    }
}
