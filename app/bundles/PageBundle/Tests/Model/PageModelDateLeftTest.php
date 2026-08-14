<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Tests\PageTestAbstract;
use Symfony\Component\HttpFoundation\Request;

final class PageModelDateLeftTest extends PageTestAbstract
{
    public function testProcessPageHitUpdatesDateLeftEvenWhenTrackingNewlyGenerated(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->once())
            ->method('updateHitDateLeft')
            ->with(42);
        $hitRepository->method('isUniquePageHit')->willReturn(false);

        $pageModel = $this->getPageModel(true, $hitRepository);

        $hit     = new Hit();
        $hit->setIpAddress(new IpAddress());
        $hit->setTrackingId('tracking-id');
        $page       = new Page();
        $reflection = new \ReflectionProperty(Page::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($page, 1);

        $contact = new Lead();
        $contact->setId(1);

        $request = Request::create('https://example.com/page');
        $request->cookies->set('mautic_referer_id', '42');

        $pageModel->processPageHit($hit, $page, $request, $contact, true);
    }
}
