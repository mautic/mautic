<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\PageBundle\Event\RedirectEvent;
use Mautic\PageBundle\EventListener\PageRedirectSubscriber;
use PHPUnit\Framework\TestCase;

class PageRedirectSubscriberTest extends TestCase
{
    /**
     * @var PrimaryCompanyHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockPrimaryCompanyHelper;

    /**
     * @var TokenHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockAssetTokenHelper;

    /**
     * @var \Mautic\PageBundle\Helper\TokenHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockPageTokenHelper;

    protected function setUp(): void
    {
        $this->mockPrimaryCompanyHelper = $this->createMock(PrimaryCompanyHelper::class);
        $this->mockAssetTokenHelper     = $this->createMock(TokenHelper::class);
        $this->mockPageTokenHelper      = $this->createMock(\Mautic\PageBundle\Helper\TokenHelper::class);
    }

    public function testPageRedirectSubscriberWithoutToken()
    {
        $lead          = new Lead();
        $redirectEvent = new RedirectEvent('http://mautic.org', $lead);

        $this->mockAssetTokenHelper
            ->expects(self::exactly(0))
            ->method('findAssetTokens');

        $this->mockPageTokenHelper
            ->expects(self::exactly(0))
            ->method('findPageTokens');

        $pageRedirectSubscriber  = new PageRedirectSubscriber(
            $this->mockPrimaryCompanyHelper,
            $this->mockAssetTokenHelper,
            $this->mockPageTokenHelper
        );

        $pageRedirectSubscriber->onRedirectReplaceTokens($redirectEvent);
    }

    public function testPageRedirectSubscriberWithTokens()
    {
        $lead = new Lead();

        $this->mockAssetTokenHelper
            ->method('findAssetTokens')
            ->willReturn(
                ['{assetlink=1}'=> 'assetUrl']
            );

        $this->mockPageTokenHelper
            ->method('findPageTokens')
            ->willReturn(
                ['{pagelink=1}' => 'pageUrl']
            );

        $redirectEvent           = new RedirectEvent('{assetlink=1}?custom', $lead);
        $pageRedirectSubscriber  = new PageRedirectSubscriber(
            $this->mockPrimaryCompanyHelper,
            $this->mockAssetTokenHelper,
            $this->mockPageTokenHelper
        );

        $pageRedirectSubscriber->onRedirectReplaceTokens($redirectEvent);
        self::assertStringContainsString('assetUrl', $redirectEvent->getUrl());

        $redirectEvent          = new RedirectEvent('{pagelink=1}?custom', $lead);
        $pageRedirectSubscriber->onRedirectReplaceTokens($redirectEvent);
        self::assertStringContainsString('pageUrl', $redirectEvent->getUrl());
    }
}
