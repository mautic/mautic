<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Twig\Extension\MenuExtension;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Assert;

class MenuExtensionTest extends AbstractMauticTestCase
{
    public function testParseMenuAttributes(): void
    {
        $menuExtension = self::$container->get(MenuExtension::class);
        \assert($menuExtension instanceof MenuExtension);

        $menuAttributes = [
            'id'    => 'myId',
            'class' => 'test-a-class test-another-class',
        ];

        Assert::assertStringStartsWith(' id=', $menuExtension->parseMenuAttributes($menuAttributes));
        Assert::assertStringContainsString('myId', $menuExtension->parseMenuAttributes($menuAttributes));
        Assert::assertStringContainsString(' class=', $menuExtension->parseMenuAttributes($menuAttributes));
        Assert::assertStringContainsString('test-a-class test-another-class', $menuExtension->parseMenuAttributes($menuAttributes));
    }
}
