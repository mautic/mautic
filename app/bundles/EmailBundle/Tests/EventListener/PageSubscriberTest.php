<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\EventListener\PageSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Event\PageHitEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class PageSubscriberTest extends TestCase
{
    private EmailModel&MockObject $emailModel;

    private RealTimeExecutioner&MockObject $realTimeExecutioner;

    private PageSubscriber $pageSubscriber;

    private Request $request;

    protected function setUp(): void
    {
        $this->emailModel          = $this->createMock(EmailModel::class);
        $this->realTimeExecutioner = $this->createMock(RealTimeExecutioner::class);

        $this->request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->pageSubscriber = new PageSubscriber(
            $this->emailModel,
            $this->realTimeExecutioner,
            $requestStack
        );
    }

    public function testOnPageHitDispatchesEmailClickForEmailRedirect(): void
    {
        $emailId = 123;
        $email   = $this->createMock(Email::class);
        $email->method('getId')->willReturn($emailId);

        $redirect = $this->createMock(Redirect::class);

        $lead = $this->createMock(Lead::class);
        $lead->method('getId')->willReturn(789);

        $hit = new Hit();
        $hit->setRedirect($redirect);
        $hit->setEmail($email);
        $hit->setLead($lead);
        $hit->setSource('email');
        $hit->setSourceId($emailId);

        $clickthrough = ['stat' => 'tracking-hash'];

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->with('tracking-hash')
            ->willReturn(null);

        $this->emailModel->expects($this->once())
            ->method('getEmailStati')
            ->with($emailId, 789)
            ->willReturn([]);

        $this->realTimeExecutioner->expects($this->once())
            ->method('execute')
            ->with(
                $this->identicalTo('email.click'),
                $this->identicalTo($hit),
                $this->identicalTo('email'),
                $this->identicalTo($emailId)
            );

        $event = new PageHitEvent($hit, $this->request, 200, $clickthrough, true);
        $this->pageSubscriber->onPageHit($event);
    }

    public function testOnPageHitDoesNotDispatchEmailClickWithoutRedirect(): void
    {
        $email = $this->createMock(Email::class);

        $hit = new Hit();
        $hit->setEmail($email);

        $this->realTimeExecutioner->expects($this->never())
            ->method('execute');

        $event = new PageHitEvent($hit, $this->request, 200, [], true);
        $this->pageSubscriber->onPageHit($event);
    }

    public function testOnPageHitMarksEmailAsReadWhenStatExists(): void
    {
        $emailId = 123;
        $email   = $this->createMock(Email::class);
        $email->method('getId')->willReturn($emailId);

        $redirect = $this->createMock(Redirect::class);

        $stat = $this->createMock(Stat::class);

        $hit = new Hit();
        $hit->setRedirect($redirect);
        $hit->setEmail($email);

        $this->emailModel->expects($this->once())
            ->method('getEmailStatus')
            ->with('tracking-hash')
            ->willReturn($stat);

        $this->emailModel->expects($this->once())
            ->method('hitEmail');

        $this->realTimeExecutioner->expects($this->once())
            ->method('execute');

        $event = new PageHitEvent($hit, $this->request, 200, ['stat' => 'tracking-hash'], true);
        $this->pageSubscriber->onPageHit($event);
    }
}
