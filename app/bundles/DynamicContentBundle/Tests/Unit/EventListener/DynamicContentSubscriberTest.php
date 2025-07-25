<?php

namespace Mautic\DynamicContentBundle\Tests\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\EventListener\DynamicContentSubscriber;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;
use PHPUnit\Framework\MockObject\MockObject;

class DynamicContentSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TrackableModel
     */
    private MockObject $trackableModel;

    /**
     * @var MockObject|PageTokenHelper
     */
    private MockObject $pageTokenHelper;

    /**
     * @var MockObject|AssetTokenHelper
     */
    private MockObject $assetTokenHelper;

    /**
     * @var MockObject|FormTokenHelper
     */
    private MockObject $formTokenHelper;

    /**
     * @var MockObject|FocusTokenHelper
     */
    private MockObject $focusTokenHelper;

    /**
     * @var MockObject|AuditLogModel
     */
    private MockObject $auditLogModel;

    /**
     * @var MockObject|DynamicContentHelper
     */
    private MockObject $dynamicContentHelper;

    /**
     * @var MockObject|DynamicContentModel
     */
    private MockObject $dynamicContentModel;

    /**
     * @var MockObject|CorePermissions
     */
    private MockObject $security;

    /**
     * @var MockObject|ContactTracker
     */
    private MockObject $contactTracker;
    private \PHPUnit\Framework\MockObject\MockObject|CompanyLeadRepository $companyLeadRepositoryMock;

    private DynamicContentSubscriber $subscriber;
    /**
     * @var CompanyModel|(CompanyModel&MockObject)|MockObject
     */
    private MockObject $companyModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackableModel            = $this->createMock(TrackableModel::class);
        $this->pageTokenHelper           = $this->createMock(PageTokenHelper::class);
        $this->assetTokenHelper          = $this->createMock(AssetTokenHelper::class);
        $this->formTokenHelper           = $this->createMock(FormTokenHelper::class);
        $this->focusTokenHelper          = $this->createMock(FocusTokenHelper::class);
        $this->auditLogModel             = $this->createMock(AuditLogModel::class);
        $this->contactTracker            = $this->createMock(ContactTracker::class);
        $this->dynamicContentHelper      = $this->createMock(DynamicContentHelper::class);
        $this->dynamicContentModel       = $this->createMock(DynamicContentModel::class);
        $this->security                  = $this->createMock(CorePermissions::class);
        $this->contactTracker            = $this->createMock(ContactTracker::class);
        $this->companyModel              = $this->createMock(CompanyModel::class);
        $this->companyLeadRepositoryMock = $this->createMock(CompanyLeadRepository::class);
        $this->subscriber                = new DynamicContentSubscriber(
            $this->trackableModel,
            $this->pageTokenHelper,
            $this->assetTokenHelper,
            $this->formTokenHelper,
            $this->focusTokenHelper,
            $this->auditLogModel,
            $this->dynamicContentHelper,
            $this->dynamicContentModel,
            $this->security,
            $this->contactTracker,
            $this->companyModel
        );
    }

    /**
     * This test is ensuring this error won't happen again:.
     *
     * DOMDocumentFragment::appendXML(): Entity: line 1: parser error : xmlParseEntityRef: no name
     *
     * It happens when there is an ampersand in the DWC content.
     */
    public function testDecodeTokensWithAmpersandDataAttribute(): void
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

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($contact);

        $this->dynamicContentHelper->expects($this->never())
            ->method('convertLeadToArray');

        $this->dynamicContentHelper->expects($this->once())
            ->method('findDwcTokens')
            ->with($content, $contact)
            ->willReturn([]);

        $this->dynamicContentHelper->expects($this->once())
            ->method('getDynamicContentForLead')
            ->with('test-token', $contact)
            ->willReturn($dwcContent);

        $event->expects($this->once())
            ->method('setContent')
            ->with($expected);

        $this->subscriber->decodeTokens($event);
    }

    /**
     * This test is ensuring this error won't happen again:.
     *
     * DOMDocumentFragment::appendXML(): Entity: line 1: parser error : xmlParseEntityRef: no name
     *
     * It happens when there is an ampersand in the DWC content.
     */
    public function testDecodeTokensWithAmpersandInlineDwc(): void
    {
        $content = <<< HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h2>Hello there!</h2>
        {dwc=test-token}
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

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($contact);

        $this->dynamicContentHelper->expects($this->never())
            ->method('convertLeadToArray');

        $this->dynamicContentHelper->expects($this->once())
            ->method('findDwcTokens')
            ->with($content, $contact)
            ->willReturn([
                '{dwc=test-token}'  => [
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

        $this->dynamicContentHelper->expects($this->never())
            ->method('getDynamicContentForLead');

        $event->expects($this->once())
            ->method('setContent')
            ->with($expected);

        $this->subscriber->decodeTokens($event);
    }

    public function testOnTokenReplacement(): void
    {
        $content = <<< HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h2>Hello there!</h2>
        Company name    : {contactfield=companyname}
        Company Country : {contactfield=companycountry}
        Company website : {contactfield=companywebsite}
    </body>
</html>
HTML;
        $expected = <<< HTML
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h2>Hello there!</h2>
        Company name    : Doe Corp
        Company Country : India
        Company website : https://www.doe.corp
    </body>
</html>
HTML;
        $contact = $this->createMock(Lead::class);
        $event   = $this->createMock(TokenReplacementEvent::class);

        $event
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($content);

        $event
            ->expects($this->once())
            ->method('getLead')
            ->willReturn($contact);

        $event
            ->expects($this->once())
            ->method('getClickthrough')
            ->willReturn([
                'slot'               => 'slotOne',
                'dynamic_content_id' => 1,
                'lead'               => 1,
            ]);

        $contact
            ->expects($this->once())
            ->method('getProfileFields')
            ->willReturn([
                'id'        => 1,
                'firstname' => 'John',
                'lastname'  => 'Doe',
                'company'   => 'Doe Corp',
                'email'     => 'john@doe.com',
            ]);

        $this->companyModel
            ->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($this->companyLeadRepositoryMock);

        $this->companyLeadRepositoryMock->expects($this->once())
            ->method('getPrimaryCompanyByLeadId')
            ->willReturn(
                [
                    'id'             => 1,
                    'companyname'    => 'Doe Corp',
                    'companycountry' => 'India',
                    'companywebsite' => 'https://www.doe.corp',
                    'is_primary'     => true,
                ]
            );

        $this->pageTokenHelper
            ->method('findPageTokens')
            ->willReturn([]);
        $this->assetTokenHelper
            ->method('findAssetTokens')
            ->willReturn([]);
        $this->formTokenHelper
            ->method('findFormTokens')
            ->willReturn([]);
        $this->focusTokenHelper
            ->method('findFocusTokens')
            ->willReturn([]);

        $this->trackableModel
            ->method('parseContentForTrackables')
            ->willReturn([
                $content,
                [],
            ]);

        $dwc = new DynamicContent();
        $dwc->setContent($content);

        $this->dynamicContentModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($dwc);

        $event->expects($this->once())
            ->method('setContent')
            ->with($expected);

        $this->subscriber->onTokenReplacement($event);
    }
}
