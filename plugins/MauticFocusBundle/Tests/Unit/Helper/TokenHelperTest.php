<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Unit\Helper;

use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
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
}
