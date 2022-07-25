<?php

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
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

        $dispatcher->dispatch(QueueEvents::PAGE_HIT, $event);

        $this->assertEquals($event->getResult(), QueueConsumerResults::ACKNOWLEDGE);
    }

    public function testOnPageHitWhenCalledRejectsBadHit()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $payload = $this->getEmptyPayload();
        $event   = new QueueConsumerEvent($payload);

        $dispatcher->dispatch(QueueEvents::PAGE_HIT, $event);

        $this->assertEquals($event->getResult(), QueueConsumerResults::REJECT);
    }

    /**
     * Get page subscriber with mocked dependencies.
     *
     * @return PageSubscriber
     */
    protected function getPageSubscriber()
    {
        $assetsHelperMock   = $this->createMock(AssetsHelper::class);
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
