<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\Tests\PageTestAbstract;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    public function testHitPageUpdatesDateLeftFromRefererCookieSynchronously(): void
    {
        $hitRepository = $this->createMock(HitRepository::class);
        $hitRepository->expects($this->once())
            ->method('updateHitDateLeft')
            ->with(42);

        $pageModel = $this->createPageModelForHitPage($hitRepository);

        $contact = new Lead();
        $contact->setId(7);

        $page    = new Page();
        $request = Request::create('https://example.com/landing');
        $request->cookies->set('mautic_referer_id', '42');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test');

        $pageModel->hitPage($page, $request, '200', $contact, ['page_url' => 'https://example.com/landing']);
    }

    private function createPageModelForHitPage(HitRepository $hitRepository): PageModel
    {
        $messageBus = new class() implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message);
            }
        };

        $cookieHelper = $this->createMock(CookieHelper::class);
        $cookieHelper->expects($this->once())
            ->method('setCookie')
            ->with(
                $this->identicalTo('mautic_referer_id'),
                $this->identicalTo(99),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->identicalTo(Cookie::SAMESITE_NONE)
            );

        $ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $device = new LeadDevice();
        $device->setTrackingId('device-tracking-id');

        $deviceTracker = $this->createMock(DeviceTracker::class);
        $deviceTracker->method('createDeviceFromUserAgent')->willReturn($device);
        $deviceTracker->method('wasDeviceChanged')->willReturn(true);

        $pageRepository = $this->createMock(PageRepository::class);
        $entityManager  = $this->createMock(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            [Page::class, $pageRepository],
            [Hit::class, $hitRepository],
        ]);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Hit $hit): bool {
                $this->setEntityId($hit, 99);

                return true;
            }));
        $entityManager->expects($this->once())->method('flush');

        $security = $this->createMock(CorePermissions::class);
        $security->method('isAnonymous')->willReturn(true);

        return new PageModel(
            $cookieHelper,
            $ipLookupHelper,
            $this->createMock(LeadModel::class),
            $this->createMock(FieldModel::class),
            $this->getRedirectModel(),
            $this->createMock(TrackableModel::class),
            $messageBus,
            $this->createMock(CompanyModel::class),
            $deviceTracker,
            $this->createMock(ContactTracker::class),
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(ContactRequestHelper::class),
            $entityManager,
            $security,
            $this->createMock(EventDispatcher::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);
    }
}
