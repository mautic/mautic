<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Assert;

class ParametersTest extends AbstractMauticTestCase
{
    public function testRememberMeParameterUsesIntProcessor(): void
    {
        Assert::assertSame(31536000, self::$container->getParameter('mautic.rememberme_lifetime'));
    }
}
