<?php

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\EventListener\BuilderSubscriber;
use Mautic\AssetBundle\Helper\TokenHelper;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuilderSubscriberTest extends TestCase
{
    private BuilderSubscriber $builderSubscriber;
    private CorePermissions|MockObject $security;
    private ContactTracker|MockObject $contactTracker;
    private TokenHelper|MockObject $tokenHelper;

    protected function setUp(): void
    {
        $this->security                  = $this->createMock(CorePermissions::class);
        $this->contactTracker            = $this->createMock(ContactTracker::class);
        $this->tokenHelper               = $this->createMock(TokenHelper::class);
        $builderTokenHelperFactory       = $this->createMock(BuilderTokenHelperFactory::class);

        $this->builderSubscriber = new BuilderSubscriber(
            $this->security,
            $this->tokenHelper,
            $this->contactTracker,
            $builderTokenHelperFactory
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
                              ['content', array_filter(json_decode('{"source":["page",null],"lead":false,"email":false}', true))],
                              ['content', array_filter(json_decode('{"source":["page",null],"lead":5,"email":false}', true))]
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
}
