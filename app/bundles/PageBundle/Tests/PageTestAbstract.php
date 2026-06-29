<?php

namespace Mautic\PageBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PageTestAbstract extends TestCase
{
    protected static $mockId   = 123;

    protected static $mockName = 'Mock test name';

    protected string $mockTrackingId;

    /**
     * @var Router|MockObject
     */
    protected ?MockObject $router = null;

    protected CorePermissions&MockObject $security;

    protected IpLookupHelper&MockObject $ipLookupHelper;

    protected ContactRequestHelper&MockObject $contactRequestHelper;

    protected CompanyModel&MockObject $companyModel;

    protected function setUp(): void
    {
        $this->mockTrackingId = hash('sha1', uniqid(mt_rand(), true));
    }

    protected function getPageModel(bool $transliterationEnabled = true, bool $validatePageHitRequiredData = true): PageModel
    {
        $cookieHelper = $this->createMock(CookieHelper::class);

        $this->router = $this->createMock(Router::class);

        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->ipLookupHelper->method('isRequestTrackable')->willReturn(true);

        $leadModel = $this->createMock(LeadModel::class);

        $leadFieldModel = $this->createMock(FieldModel::class);

        $redirectModel = $this->getRedirectModel();

        $this->companyModel = $this->createMock(CompanyModel::class);

        $trackableModel = $this->createMock(TrackableModel::class);

        $dispatcher = $this->createMock(EventDispatcher::class);

        $translator = $this->createMock(Translator::class);

        $entityManager = $this->createMock(EntityManager::class);

        $pageRepository = $this->createMock(PageRepository::class);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $hitRepository = $this->createMock(HitRepository::class);
        $userHelper    = $this->createMock(UserHelper::class);

        $messageBus = $this->createMock(MessageBus::class);

        $contactTracker = $this->createMock(ContactTracker::class);

        $this->contactRequestHelper = $this->createMock(ContactRequestHelper::class);

        $lead = new Lead();
        $lead->setId(self::$mockId);
        $lead->setFirstname(self::$mockName);

        $contactTracker->expects($this
            ->any())
            ->method('getContact')
            ->willReturn($lead);

        $entityManager->expects($this
            ->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [\Mautic\PageBundle\Entity\Page::class, $pageRepository],
                    [\Mautic\PageBundle\Entity\Hit::class, $hitRepository],
                ]
            );

        $coreParametersHelper->expects($this->any())
            ->method('get')
            ->with($this->anything())
            ->willReturnCallback(function ($parameter) use ($transliterationEnabled, $validatePageHitRequiredData) {
                if ('transliterate_page_title' === $parameter) {
                    return $transliterationEnabled;
                }

                if ('validate_page_hit_required_data' === $parameter) {
                    return $validatePageHitRequiredData;
                }
            });

        $deviceTrackerMock           = $this->createMock(DeviceTracker::class);
        $statRepositoryMock          = $this->createMock(StatRepository::class);
        $botRatioHelperMock          = $this->createMock(BotRatioHelper::class);
        $validatorMock               = $this->createMock(ValidatorInterface::class);

        $validatorMock->method('validate')
            ->willReturn(new ConstraintViolationList());

        $pageModel = new PageModel(
            $cookieHelper,
            $this->ipLookupHelper,
            $leadModel,
            $leadFieldModel,
            $redirectModel,
            $trackableModel,
            $messageBus,
            $this->companyModel,
            $deviceTrackerMock,
            $contactTracker,
            $coreParametersHelper,
            $this->contactRequestHelper,
            $this->createMock(\Mautic\CoreBundle\Model\AbTest\VariantConverterService::class),
            $entityManager,
            $this->security = $this->createMock(CorePermissions::class),
            $dispatcher,
            $this->router,
            $translator,
            $userHelper,
            $this->createMock(LoggerInterface::class),
            $statRepositoryMock,
            $botRatioHelperMock,
            $validatorMock
        );

        return $pageModel;
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel(): MockObject
    {
        $shortener = $this->createMock(Shortener::class);

        $mockRedirectModel = $this->getMockBuilder(RedirectModel::class)
            ->setConstructorArgs([
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(CorePermissions::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(UrlGeneratorInterface::class),
                $this->createMock(Translator::class),
                $this->createMock(UserHelper::class),
                $this->createMock(LoggerInterface::class),
                $this->createMock(CoreParametersHelper::class),
                $shortener,
            ])
            ->onlyMethods(['createRedirectEntity', 'generateRedirectUrl'])
            ->getMock();

        $mockRedirect = $this->createMock(\Mautic\PageBundle\Entity\Redirect::class);

        $mockRedirectModel->expects($this->any())
            ->method('createRedirectEntity')
            ->willReturn($mockRedirect);

        $mockRedirectModel->expects($this->any())
            ->method('generateRedirectUrl')
            ->willReturn('http://some-url.com');

        return $mockRedirectModel;
    }
}
