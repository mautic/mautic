<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\QueueBundle\Event\QueueConsumerEvent;
use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\QueueEvents;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class PageSubscriberTest extends WebTestCase
{
    public function testGetTokens_WhenCalled_ReturnsValidTokens()
    {
        $translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()
            ->getMock();

        $pageBuilderEvent = new PageBuilderEvent($translator);
        $pageBuilderEvent->addToken('{token_test}', 'TOKEN VALUE');
        $tokens = $pageBuilderEvent->getTokens();
        $this->assertArrayHasKey('{token_test}', $tokens);
        $this->assertEquals($tokens['{token_test}'], 'TOKEN VALUE');
    }

    public function testOnPageHit_WhenCalled_AcknowledgesHit()
    {
        $dispatcher = new EventDispatcher();
        $subscriber = $this->getPageSubscriber();

        $dispatcher->addSubscriber($subscriber);

        $payload = $this->getNonEmptyPayload();
        $event   = new QueueConsumerEvent($payload);

        $dispatcher->dispatch(QueueEvents::PAGE_HIT, $event);

        $this->assertEquals($event->getResult(), QueueConsumerResults::ACKNOWLEDGE);
    }

    public function testOnPageHit_WhenCalled_RejectsBadHit()
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
        $entityManagerMock  = $this->createMock(EntityManager::class);
        $hitRepository      = $this->createMock(EntityRepository::class);
        $pageRepository     = $this->createMock(EntityRepository::class);
        $leadRepository     = $this->createMock(EntityRepository::class);
        $hitMock            = $this->createMock(Hit::class);
        $leadMock           = $this->createMock(Lead::class);

        $hitRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($hitMock));

        $leadRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($leadMock));

        $entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['MauticPageBundle:Hit', $hitRepository],
                ['MauticPageBundle:Page', $pageRepository],
                ['MauticLeadBundle:Lead', $leadRepository],
            ]));

        $pageSubscriber = new PageSubscriber(
            $assetsHelperMock,
            $ipLookupHelperMock,
            $auditLogModelMock,
            $pageModelMock
        );

        $pageSubscriber->setEntityManager($entityManagerMock);

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
