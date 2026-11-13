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

    public function testTokenUsesTheFocusScript(): void
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
     * @return iterable<string, array{string}>
     */
    public static function invalidTokenProvider(): iterable
    {
        yield 'zero' => ['{focus=0}'];
        yield 'negative' => ['{focus=-1}'];
        yield 'non-decimal' => ['{focus=1.5}'];
        yield 'not a number' => ['{focus=invalid}'];
        yield 'with a mode' => ['{focus=12|display}'];
        yield 'missing ID' => ['{focus=}'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTokenProvider')]
    public function testInvalidTokenResolvesToEmptyWithoutLoadingFocusItem(string $token): void
    {
        $this->model->expects($this->never())
            ->method('getEntity');

        $this->assertSame([$token => ''], $this->helper->findFocusTokens('content '.$token));
    }

    public function testUnclosedTokenDoesNotSwallowTheContentAfterIt(): void
    {
        $this->model->expects($this->once())
            ->method('getEntity')
            ->with(1)
            ->willReturn(null);

        $this->assertSame(
            ['{focus=1}' => ''],
            $this->helper->findFocusTokens("Intro {focus=oops\n<p>Page content</p>\n{focus=1}"),
        );
    }

    public function testPublicRegexStillCapturesTheTokenValue(): void
    {
        // Plugins that use the constant read the ID from the first capture group
        $this->assertSame(1, preg_match(TokenHelper::REGEX, 'Before {focus=5} after', $matches));
        $this->assertSame('5', $matches[1]);
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
            ['{focus=1}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'],
            $this->helper->findFocusTokens('{focus=1} {focus=1}'),
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
