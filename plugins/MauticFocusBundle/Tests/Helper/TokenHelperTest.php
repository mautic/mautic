<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Tests\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use MauticPlugin\MauticFocusBundle\Entity\Focus;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class TokenHelperTest extends TestCase
{
    /**
     * @var FocusModel|MockObject
     */
    private $model;

    /**
     * @var MockObject|RouterInterface
     */
    private $router;

    /**
     * @var CorePermissions|MockObject
     */
    private $security;

    /**
     * @var TokenHelper
     */
    private $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model    = $this->createMock(FocusModel::class);
        $this->router   = $this->createMock(RouterInterface::class);
        $this->security = $this->createMock(CorePermissions::class);

        $this->helper = new TokenHelper($this->model, $this->router, $this->security);
    }

    public function testFindFocusTokensNotFound(): void
    {
        $content = 'content';

        self::assertSame([], $this->helper->findFocusTokens($content));
    }

    public function testFindFocusTokensFound(): void
    {
        $content = 'content {focus=1}';

        self::assertSame(['{focus=1}' => ''], $this->helper->findFocusTokens($content));
    }

    public function testFindFocusTokensFoundAddScriptByFocusPublishedStatus(): void
    {
        $focusItemId = 1;
        $content     = "content {focus=$focusItemId}";

        $focusItem = new Focus();
        $focusItem->setIsPublished(true);

        $this->model->expects(self::once())
            ->method('getEntity')
            ->with($focusItemId)
            ->willReturn($focusItem);

        self::assertSame(
            ['{focus=1}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'],
            $this->helper->findFocusTokens($content)
        );
    }

    public function testFindFocusTokensFoundAddScriptByAccessCheck(): void
    {
        $focusItemId = 1;
        $createdById = 2;
        $content     = "content {focus=$focusItemId}";

        $focusItem = new Focus();
        $focusItem->setIsPublished(false);
        $focusItem->setCreatedBy($createdById);

        $this->model->expects(self::once())
            ->method('getEntity')
            ->with($focusItemId)
            ->willReturn($focusItem);

        $this->security->expects(self::once())
            ->method('hasEntityAccess')
            ->with(
            'focus:items:viewown',
                'focus:items:viewother',
                $focusItem->getCreatedBy()
            )
            ->willReturn(true);

        self::assertSame(
            ['{focus=1}' => '<script src="" type="text/javascript" charset="utf-8" async="async"></script>'],
            $this->helper->findFocusTokens($content)
        );
    }
}
