<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use PHPUnit\Framework\TestCase;

class BuilderSubscriberTest extends TestCase
{
    /**
     * @var BuilderSubscriber
     */
    private $builderSubscriber;

    /**
     * @var CorePermissions|\PHPUnit\Framework\MockObject\MockObject
     */
    private $security;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    protected function setUp(): void
    {
        $this->security                  = $this->createMock(CorePermissions::class);
        $this->contactTracker            = $this->createMock(ContactTracker::class);
        $this->tokenHelper               = $this->createMock(TokenHelper::class);
        $this->builderTokenHelperFactory = $this->createMock(BuilderTokenHelperFactory::class);

        $this->builderSubscriber = new BuilderSubscriber(
            $this->security,
            $this->tokenHelper,
            $this->contactTracker,
            $this->builderTokenHelperFactory
        );
    }

    public function testOnPageDisplay(): void
    {
        $this->security->expects($this->exactly(2))
                       ->method('isAnonymous')
                       ->willReturn(true);

        $this->tokenHelper->expects($this->exactly(2))
                          ->method('findAssetTokens')
                          ->withConsecutive(
                              ['content', array_filter(json_decode('{"source":["page",null],"lead":false,"email":false}', JSON_OBJECT_AS_ARRAY))],
                              ['content', array_filter(json_decode('{"source":["page",null],"lead":5,"email":false}', JSON_OBJECT_AS_ARRAY))]
                          );

        $page             = new Page();
        $pageDisplayEvent = new PageDisplayEvent('content', $page, [], true);

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
             ->method('getId')
             ->willReturn(5);

        $this->contactTracker->expects($this->once())
             ->method('getContact')
             ->willReturn($lead);

        $this->builderSubscriber->onPageDisplay($pageDisplayEvent);
        $this->builderSubscriber->onPageDisplay($pageDisplayEvent->setTrackingDisabled(false));
    }

    public function testDecodeTokensNotRunIfEventHasNoTrackingDisabled(): void
    {
        $this->security->expects($this->once())
                       ->method('isAnonymous')
                       ->willReturn(true);

        $this->tokenHelper->expects($this->never())
                          ->method('findAssetTokens');

        $eventMock = $this->getMockBuilder(PageDisplayEvent::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $eventMock->expects($this->never())->method('getContent');

        $page             = new Page();
        $pageDisplayEvent = new PageDisplayEvent('content', $page, [], false);

        $this->builderSubscriber->onPageDisplay($pageDisplayEvent);
    }
}
