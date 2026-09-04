<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Entity\FocusRepository;
use MauticPlugin\MauticFocusBundle\EventListener\PageSubscriber;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PageSubscriberTest extends TestCase
{
    public function testPickerProvidesDisplayTokenFirstAndTrackingToken(): void
    {
        $model = $this->createStub(FocusModel::class);
        $model->method('getPermissionBase')->willReturn('focus:items');

        $repository = $this->createStub(FocusRepository::class);
        $repository->method('getTableAlias')->willReturn('f');
        $repository->method('getSimpleList')->willReturn([['value' => 7, 'label' => 'Test']]);
        $model->method('getRepository')->willReturn($repository);

        $security = $this->createStub(CorePermissions::class);
        $security->method('isGranted')->willReturn(['focus:items:viewown' => true, 'focus:items:viewother' => true]);

        $modelFactory = $this->createStub(ModelFactory::class);
        $modelFactory->method('getModel')->willReturn($model);

        $connection = $this->createStub(Connection::class);
        $connection->method('createExpressionBuilder')->willReturn(new ExpressionBuilder($connection));

        $factory = $this->createStub(BuilderTokenHelperFactory::class);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $key): string => match ($key) {
                'mautic.focus.focus_item'    => 'Focus Item',
                'mautic.focus.token.display'  => '(display only)',
                'mautic.focus.token.tracking' => '(tracking)',
                default                        => $key,
            },
        );

        $builderTokenHelper = new BuilderTokenHelper(
            $security,
            $modelFactory,
            $connection,
            $this->createStub(UserHelper::class),
            $translator,
        );
        $builderTokenHelper->configure('focus', 'focus:items');
        $factory->method('getBuilderTokenHelper')->willReturn($builderTokenHelper);

        $subscriber = new PageSubscriber(
            $model,
            new TokenHelper($model, $this->createStub(RouterInterface::class), $this->createStub(CorePermissions::class)),
            $factory,
            $translator,
        );
        $event = new PageBuilderEvent($translator, null, 'tokens');

        $subscriber->onPageBuild($event);

        $this->assertSame([
            '{focus=7|display}' => 'Focus Item: Test (display only)',
            '{focus=7|tracking}' => 'Focus Item: Test (tracking)',
        ], $event->getTokens());
    }

    public function testPageDisplayReplacesDisplayTrackingAndUnqualifiedTokens(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $model = $this->createMock(FocusModel::class);
        $model->expects($this->exactly(3))
            ->method('getEntity')
            ->willReturn($focusItem);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => match ($route) {
                'mautic_focus_generate'         => 'https://example.test/focus/'.$parameters['id'].'.js',
                'mautic_focus_generate_display' => 'https://example.test/focus/'.$parameters['id'].'/display.js',
                default                         => throw new \UnexpectedValueException($route),
            });

        $tokenHelper = new TokenHelper($model, $router, $this->createStub(CorePermissions::class));
        $content     = '{focus=1|tracking} {focus=2|display} {focus=3} {focus=4|legacy}';

        $subscriber = new PageSubscriber(
            $model,
            $tokenHelper,
            $this->createStub(BuilderTokenHelperFactory::class),
            $this->createStub(TranslatorInterface::class),
        );
        $event = new PageDisplayEvent($content, new Page());

        $subscriber->onPageDisplay($event);

        $this->assertSame(
            '<script src="https://example.test/focus/1.js" type="text/javascript" charset="utf-8" async="async"></script> '
            .'<script src="https://example.test/focus/2/display.js" type="text/javascript" charset="utf-8" async="async"></script> '
            .'<script src="https://example.test/focus/3.js" type="text/javascript" charset="utf-8" async="async"></script> ',
            $event->getContent(),
        );
    }
}
