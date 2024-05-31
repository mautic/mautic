<?php

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class PageSubscriberTest extends TestCase
{
    public function testGetTokensWhenCalledReturnsValidTokens()
    {
        $translator       = $this->createMock(Translator::class);
        $pageBuilderEvent = new PageBuilderEvent($translator);
        $pageBuilderEvent->addToken('{token_test}', 'TOKEN VALUE');
        $tokens = $pageBuilderEvent->getTokens();
        $this->assertArrayHasKey('{token_test}', $tokens);
        $this->assertEquals($tokens['{token_test}'], 'TOKEN VALUE');
    }

    public function testOnPageHitWhenCalledAcknowledgesHit()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $payload = $this->getNonEmptyPayload();
        $event   = new QueueConsumerEvent($payload);

        $dispatcher->dispatch($event, QueueEvents::PAGE_HIT);

        $this->assertEquals($event->getResult(), QueueConsumerResults::ACKNOWLEDGE);
    }

    public function testOnPageHitWhenCalledRejectsBadHit()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $payload = $this->getEmptyPayload();
        $event   = new QueueConsumerEvent($payload);

        $dispatcher->dispatch($event, QueueEvents::PAGE_HIT);

        $this->assertEquals($event->getResult(), QueueConsumerResults::REJECT);
    }

    public function testOnPageDisplayBodyTagRegex()
    {
        $dummyPageContent = <<<EOF
<html>
    <head>
    </head>
    <body class="mt-6 md:max-w-2xl p-[5px]"  onclick="myFunction()" data-help-text="téxt with nön äscii charactêrs">
    </body>
</html>
EOF;
        $event = new PageDisplayEvent(
            $dummyPageContent,
            $this->createMock(Page::class)
        );
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $dispatcher->dispatch($event, PageEvents::PAGE_ON_DISPLAY);

        $this->assertEquals(
            $event->getContent(),
            <<<EOF
<html>
    <head>
    </head>
    <body class="mt-6 md:max-w-2xl p-[5px]"  onclick="myFunction()" data-help-text="téxt with nön äscii charactêrs">
<script data-source="mautic">
const foo='bar';
</script>

    </body>
</html>
EOF
        );
    }

    /**
     * Get page subscriber with mocked dependencies.
     *
     * @return PageSubscriber
     */
    protected function getPageSubscriber()
    {
        $packagesMock = $this->getMockBuilder(Packages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assetsHelperMock   = new AssetsHelper($packagesMock);
        $ipLookupHelperMock = $this->createMock(IpLookupHelper::class);
        $auditLogModelMock  = $this->createMock(AuditLogModel::class);
        $pageModelMock      = $this->createMock(PageModel::class);
        $logger             = $this->createMock(Logger::class);
        $hitRepository      = $this->createMock(HitRepository::class);
        $pageRepository     = $this->createMock(PageRepository::class);
        $redirectRepository = $this->createMock(RedirectRepository::class);
        $contactRepository  = $this->createMock(LeadRepository::class);
        $hitMock            = $this->createMock(Hit::class);
        $leadMock           = $this->createMock(Lead::class);

        $assetsHelperMock->addScriptDeclaration("const foo='bar';", 'onPageDisplay_bodyOpen');

        $hitRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($hitMock));

        $contactRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($leadMock));

        $pageSubscriber = new PageSubscriber(
            $assetsHelperMock,
            $ipLookupHelperMock,
            $auditLogModelMock,
            $pageModelMock,
            $logger,
            $hitRepository,
            $pageRepository,
            $redirectRepository,
            $contactRepository
        );

        return $pageSubscriber;
    }

    /**
     * Get non empty payload, having a Request and non-null entity IDs.
     *
     * @return array
     */
    protected function getNonEmptyPayload()
    {
        $requestMock = $this->createMock(Request::class);

        return [
            'request' => $requestMock,
            'isNew'   => true,
            'hitId'   => 123,
            'pageId'  => 456,
            'leadId'  => 789,
        ];
    }

    /**
     * Get empty payload with all null entity IDs.
     *
     * @return array
     */
    protected function getEmptyPayload()
    {
        return array_fill_keys(['request', 'isNew', 'hitId', 'pageId', 'leadId'], null);
    }
}
