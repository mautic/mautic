<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Tests\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\EventListener\DynamicContentSubscriber;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;

class DynamicContentSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private $trackableModel;
    private $pageTokenHelper;
    private $assetTokenHelper;
    private $formTokenHelper;
    private $focusTokenHelper;
    private $auditLogModel;
    private $leadModel;
    private $dynamicContentHelper;
    private $dynamicContentModel;
    private $subscriber;

    protected function setUp()
    {
        parent::setUp();

        $this->trackableModel       = $this->createMock(TrackableModel::class);
        $this->pageTokenHelper      = $this->createMock(PageTokenHelper::class);
        $this->assetTokenHelper     = $this->createMock(AssetTokenHelper::class);
        $this->formTokenHelper      = $this->createMock(FormTokenHelper::class);
        $this->focusTokenHelper     = $this->createMock(FocusTokenHelper::class);
        $this->auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->leadModel            = $this->createMock(LeadModel::class);
        $this->dynamicContentHelper = $this->createMock(DynamicContentHelper::class);
        $this->dynamicContentModel  = $this->createMock(DynamicContentModel::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $this->subscriber           = new DynamicContentSubscriber(
            $this->trackableModel,
            $this->pageTokenHelper,
            $this->assetTokenHelper,
            $this->formTokenHelper,
            $this->focusTokenHelper,
            $this->auditLogModel,
            $this->leadModel,
            $this->dynamicContentHelper,
            $this->dynamicContentModel
        );

        $this->subscriber->setSecurity($this->security);
    }

    /**
     * This test is ensuring this error won't happen again:.
     *
     * DOMDocumentFragment::appendXML(): Entity: line 1: parser error : xmlParseEntityRef: no name
     *
     * It happens when there is an ampersand in the DWC content.
     */
    public function testDecodeTokensWithAmpersand()
    {
        $content = <<< HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h2>Hello there!</h2>
        <div data-slot="dwc" data-param-slot-name="test-token"></div>
    </body>
</html>

HTML;

        $expected = <<< HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h2>Hello there!</h2>
        <a href="https://john.doe&son">Link</a>
    </body>
</html>

HTML;
        $dwcContent = '<a href="https://john.doe&son">Link</a>';
        $event      = $this->createMock(PageDisplayEvent::class);
        $contact    = new Lead();

        $event->expects($this->once())
            ->method('getContent')
            ->willReturn($content);

        $this->security->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);

        $this->leadModel->expects($this->once())
            ->method('getCurrentLead')
            ->willReturn($contact);

        $this->dynamicContentHelper->expects($this->once())
            ->method('convertLeadToArray')
            ->with($contact)
            ->willReturn(['id' => 123, 'email' => 'john@doe.email']);

        $this->dynamicContentHelper->expects($this->once())
            ->method('findDwcTokens')
            ->with($content, $contact)
            ->willReturn([
                'test-token'  => [
                    'content' => $dwcContent,
                    'filters' => [
                        [
                            'field'    => 'email',
                            'operator' => '!empty',
                            'filter'   => '',
                            'type'     => 'email',
                        ],
                    ],
                ],
            ]);

        $this->dynamicContentHelper->expects($this->once())
            ->method('getDynamicContentForLead')
            ->willReturn($dwcContent);

        $event->expects($this->once())
            ->method('setContent')
            ->with($expected);

        $this->subscriber->decodeTokens($event);
    }
}
