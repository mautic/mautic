<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\MenuFactory;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use Mautic\CoreBundle\Menu\MenuBuilder;
use Mautic\CoreBundle\Menu\MenuHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuBuilderTest extends TestCase
{
    private FactoryInterface $factory;

    private MatcherInterface&MockObject $matcher;

    private EventDispatcherInterface&MockObject $dispatcher;

    private MenuHelper&MockObject $menuHelper;

    protected function setUp(): void
    {
        $this->factory    = new MenuFactory();
        $this->matcher    = $this->createMock(MatcherInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->menuHelper = $this->createMock(MenuHelper::class);
    }

    public function testMainMenuKeepsUriOnlyItems(): void
    {
        $menuName = uniqid('uriOnlyItems', false);

        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (MenuEvent $event, string $eventName): MenuEvent {
                Assert::assertSame(CoreEvents::BUILD_MENU, $eventName);

                $event->addMenuItems([
                    'mautic.contribute.menu.index' => [
                        'uri'       => 'https://mautic.org',
                        'priority'  => 0,
                        'iconClass' => 'ri-hand-coin-fill',
                    ],
                ]);

                return $event;
            });

        $builder = new MenuBuilder($this->factory, $this->matcher, $this->dispatcher, $this->menuHelper);

        $menu = $builder->__call($menuName, []);

        Assert::assertInstanceOf(ItemInterface::class, $menu);
        $menuItem = $menu->getChild('mautic.contribute.menu.index');

        Assert::assertInstanceOf(ItemInterface::class, $menuItem);
        Assert::assertSame('https://mautic.org', $menuItem->getUri());
    }
}
