<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class TokenHelperTest extends TestCase
{
    /**
     * @var MockObject&FocusModel
     */
    private MockObject $model;

    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $security;

    private TokenHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model    = $this->createMock(FocusModel::class);
        $this->security = $this->createMock(CorePermissions::class);

        $this->helper = new TokenHelper($this->model, $this->createStub(RouterInterface::class), $this->security);
    }

    public function testFindFocusTokensNotFound(): void
    {
        $content = 'content';

        $this->assertSame([], $this->helper->findFocusTokens($content));
    }

    public function testFindFocusTokensFound(): void
    {
        $content = 'content {focus=1}';

        $this->assertSame(['{focus=1}' => ''], $this->helper->findFocusTokens($content));
    }

    public function testFindFocusTokensFoundAddScriptByFocusPublishedStatus(): void
    {
        $focusItemId = 1;
        $content     = "content {focus={$focusItemId}}";

        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with($focusItemId)
            ->willReturn($focusItem);

        $this->assertSame(['{focus=1}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'], $this->helper->findFocusTokens($content));
    }

    public function testFindFocusTokensDisplayModeUsesDisplayEndpoint(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($focusItem);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_focus_generate_display',
                ['id' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.test/focus/1/display.js');

        $helper = new TokenHelper($this->model, $router, $this->security);

        $this->assertSame(
            ['{focus=1|display}' => '<script src="https://example.test/focus/1/display.js" type="text/javascript" charset="utf-8" async="async"></script>'],
            $helper->findFocusTokens('content {focus=1|display}'),
        );
    }

    public function testTrackingModeUsesAggregateEndpoint(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($focusItem);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_focus_generate',
                ['id' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.test/focus/1.js');

        $helper = new TokenHelper($this->model, $router, $this->security);

        $this->assertSame(
            ['{focus=1|tracking}' => '<script src="https://example.test/focus/1.js" type="text/javascript" charset="utf-8" async="async"></script>'],
            $helper->findFocusTokens('content {focus=1|tracking}'),
        );
    }

    public function testUnqualifiedTokenUsesAggregateEndpoint(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($focusItem);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_focus_generate',
                ['id' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.test/focus/1.js');

        $helper = new TokenHelper($this->model, $router, $this->security);

        $this->assertSame(
            ['{focus=1}' => '<script src="https://example.test/focus/1.js" type="text/javascript" charset="utf-8" async="async"></script>'],
            $helper->findFocusTokens('content {focus=1}'),
        );
    }

    /**
     * @param array{id: int, mode: string}|null $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('tokenProvider')]
    public function testParseToken(string $token, ?array $expected): void
    {
        $this->assertSame($expected, $this->helper->parseToken($token));
    }

    public static function tokenProvider(): iterable
    {
        yield 'unqualified tracking' => ['{focus=12}', ['id' => 12, 'mode' => TokenHelper::MODE_TRACKING]];
        yield 'display' => ['{focus=12|display}', ['id' => 12, 'mode' => TokenHelper::MODE_DISPLAY]];
        yield 'tracking' => ['{focus=12|tracking}', ['id' => 12, 'mode' => TokenHelper::MODE_TRACKING]];
        yield 'case and mode whitespace' => ['{FOCUS=12 | DISPLAY }', ['id' => 12, 'mode' => TokenHelper::MODE_DISPLAY]];
        yield 'zero' => ['{focus=0}', null];
        yield 'negative' => ['{focus=-1}', null];
        yield 'non-decimal' => ['{focus=1.5}', null];
        yield 'unknown mode' => ['{focus=12|legacy}', null];
        yield 'missing ID' => ['{focus=|display}', null];
    }

    public function testInvalidModesResolveToEmptyWithoutLoadingFocusItem(): void
    {
        $this->model->expects($this->never())
            ->method('getEntity');

        $this->assertSame(
            ['{focus=1|legacy}' => '', '{focus=invalid}' => ''],
            $this->helper->findFocusTokens('{focus=1|legacy} {focus=invalid}'),
        );
    }

    public function testFormatTokenRejectsLegacyMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->helper->formatToken(1, 'legacy');
    }

    public function testDuplicateTokensAreResolvedOnce(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($focusItem);

        $this->assertSame(
            ['{focus=1|display}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'],
            $this->helper->findFocusTokens('{focus=1|display} {focus=1|display}'),
        );
    }

    public function testFindFocusTokensFoundAddScriptByAccessCheck(): void
    {
        $focusItemId = 1;
        $createdById = 2;
        $content     = "content {focus={$focusItemId}}";

        $focusItem = new Focus();
        $focusItem->setIsPublished(false);
        $focusItem->setCreatedBy($createdById);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with($focusItemId)
            ->willReturn($focusItem);

        $this->security->expects($this->once())
            ->method('hasEntityAccess')
            ->with(
                'focus:items:viewown',
                'focus:items:viewother',
                $focusItem->getCreatedBy()
            )
            ->willReturn(true);

        $this->assertSame(['{focus=1}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'], $this->helper->findFocusTokens($content));
    }

    public function testUnpublishedUnauthorizedFocusItemResolvesToEmpty(): void
    {
        $focusItem = new Focus();
        $focusItem->setIsPublished(false);
        $focusItem->setCreatedBy(2);

        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($focusItem);
        $this->security->expects($this->once())
            ->method('hasEntityAccess')
            ->willReturn(false);

        $this->assertSame(['{focus=1}' => ''], $this->helper->findFocusTokens('{focus=1}'));
    }
}
