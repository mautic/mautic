<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Assert;

class ParametersTest extends AbstractMauticTestCase
{
    public function testRememberMeParameterUsesIntProcessor(): void
    {
        Assert::assertSame(7_776_000, static::getContainer()->getParameter('mautic.rememberme_lifetime'));
    }
}
