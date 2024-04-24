<?php

namespace Mautic\MessengerBundle\Tests\MessageHandler;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class PageHitNotificationHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        [$hitId, $pageId, $leadId, $redirectId]                 = [random_int(1, 1000), random_int(1, 1000), random_int(1, 1000), random_int(1, 1000)];

        $redirectObject = new Redirect();
        $redirectObject->setRedirectId((string) $redirectId);

        [$hitObject, $pageObject, $leadObject] = [
            (new Hit())->setCode(7),
            (new Page())->setAlias('james_bond'),
            (new Lead())->setId($leadId),
        ];

        $hitRepoMock = $this->createMock(HitRepository::class);
        $hitRepoMock
            ->expects($this->once())
            ->method('find')
            ->with($hitId)
            ->willReturn($hitObject);

        $pageRepoMock = $this->createMock(PageRepository::class);
        $pageRepoMock->expects($this->once())
            ->method('find')
            ->with($pageId)
            ->willReturn($pageObject);

        $redirectRepoMock = $this->createMock(RedirectRepository::class);
        $redirectRepoMock
            ->expects($this->never())
            ->method('find')
            ->with($redirectId)
            ->willReturn($redirectObject);

        $leadRepoMock = $this->createMock(LeadRepository::class);
        $leadRepoMock
            ->expects($this->once())
            ->method('find')
            ->with($leadId)
            ->willReturn($leadObject);

        $request = new Request();
        $request->query->set('testMe', 'I am here');

        /** @var MockObject|PageModel $pageModelMock */
        $pageModelMock = $this->createMock(PageModel::class);
        $pageModelMock
            ->expects($this->exactly(1))
            ->method('processPageHit')
            ->with($hitObject, $pageObject, $request, $leadObject, false, false);

        $message = new PageHitNotification($hitId, $request, false, false, $pageId, $leadId);

        /** @var MockObject|LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $handler = new PageHitNotificationHandler(
            $pageRepoMock, $hitRepoMock, $leadRepoMock, $loggerMock, $redirectRepoMock, $pageModelMock
        );

        $handler->__invoke($message);
    }
}
