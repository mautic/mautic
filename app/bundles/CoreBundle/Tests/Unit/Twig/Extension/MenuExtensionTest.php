<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Twig\Extension\MenuExtension;

final class MenuExtensionTest extends AbstractMauticTestCase
{
    public function testParseMenuAttributes(): void
    {
        $menuExtension = self::getContainer()->get(MenuExtension::class);
        $this->assertInstanceOf(MenuExtension::class, $menuExtension);

        $menuAttributes = [
            'id'    => 'myId',
            'class' => 'test-a-class test-another-class',
        ];

        $this->assertStringStartsWith(' id=', $menuExtension->parseMenuAttributes($menuAttributes));
        $this->assertStringContainsString('myId', $menuExtension->parseMenuAttributes($menuAttributes));
        $this->assertStringContainsString(' class=', $menuExtension->parseMenuAttributes($menuAttributes));
        $this->assertStringContainsString('test-a-class test-another-class', $menuExtension->parseMenuAttributes($menuAttributes));
    }

    public function testBuildMenuClasses(): void
    {
        $menuExtension = self::getContainer()->get(MenuExtension::class);
        $this->assertInstanceOf(MenuExtension::class, $menuExtension);

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
        $this->assertInstanceOf(ItemInterface::class, $itemFirst);

        // test an item which has no class
        $this->assertSame([], $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));
        $this->assertInstanceOf(ItemInterface::class, $itemSecond);

        // test an item with an inherrent class
        $this->assertArrayHasKey('class', $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));
        $this->assertSame(['class' => 'test-class'], $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));

        // test an item with an 'extra' class
        $extraClasses = 'extra-class';
        $this->assertArrayHasKey('class', $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));
        $this->assertSame(['class' => 'extra-class'], $menuExtension->buildMenuClasses($itemFirst, $matcher, $options, $extraClasses));
        $this->assertSame(['class' => 'test-class extra-class'], $menuExtension->buildMenuClasses($itemSecond, $matcher, $options, $extraClasses));
    }
}
