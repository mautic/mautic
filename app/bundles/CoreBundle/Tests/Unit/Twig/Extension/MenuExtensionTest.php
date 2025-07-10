<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Knp\Menu\MenuFactory;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Twig\Extension\MenuExtension;
use PHPUnit\Framework\Assert;

class MenuExtensionTest extends AbstractMauticTestCase
{
    public function testParseMenuAttributes(): void
    {
        $menuExtension = static::getContainer()->get(MenuExtension::class);
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

    public function testBuildMenuClasses(): void
    {
        $menuExtension = static::getContainer()->get(MenuExtension::class);
        \assert($menuExtension instanceof MenuExtension);

        // create a menu and menu items to test with
        $factory = new MenuFactory();
        $menu    = $factory->createItem('My menu');
        $menu->addChild('First item', ['uri' => '/']);
        $menu->addChild('Second item', ['uri' => '/', 'attributes' => ['class' => 'test-class']]);

        $matcher        = null;
        $options        = [];
        $extraClasses   = '';

        $itemFirst  = $menu->getChild('First item');
        $itemSecond = $menu->getChild('Second item');

        // test an item which has no class
        Assert::assertEquals([], $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));

        // test an item with an inherrent class
        Assert::assertArrayHasKey('class', $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));
        Assert::assertEquals(['class' => 'test-class'], $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));

        // test an item with an 'extra' class
        $extraClasses = 'extra-class';
        Assert::assertArrayHasKey('class', $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));
        Assert::assertEquals(['class' => 'extra-class'], $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));
        Assert::assertEquals(['class' => 'test-class extra-class'], $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));
    }
}
