<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbTest\VariantConverterService;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Shortener\Shortener;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\UtmTagRepository;
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
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Entity\TrackableRepository;
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

abstract class PageTestAbstract extends TestCase
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
        $this->mockTrackingId = hash('sha1', uniqid((string) mt_rand(), true));
    }

    protected function getPageModel(bool $transliterationEnabled = true, bool $validatePageHitRequiredData = true): PageModel
    {
        $this->router = $this->createMock(Router::class);

        $this->ipLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->ipLookupHelper->method('isRequestTrackable')->willReturn(true);

        $redirectModel = $this->getRedirectModel();

        $this->companyModel = $this->createMock(CompanyModel::class);

        $entityManager = $this->createMock(EntityManager::class);

        $pageRepository = $this->createMock(PageRepository::class);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $hitRepository = $this->createMock(HitRepository::class);

        $contactTracker = $this->createMock(ContactTracker::class);

        $this->contactRequestHelper = $this->createMock(ContactRequestHelper::class);

        $lead = new Lead();
        $lead->setId(self::$mockId);
        $lead->setFirstname(self::$mockName);

        $contactTracker
            ->method('getContact')
            ->willReturn($lead);

        $entityManager
            ->method('getRepository')
            ->willReturnMap(
                [
                    [Page::class, $pageRepository],
                    [Hit::class, $hitRepository],
                ]
            );

        $coreParametersHelper
            ->method('get')
            ->willReturnCallback(function ($parameter) use ($transliterationEnabled, $validatePageHitRequiredData) {
                if ('transliterate_page_title' === $parameter) {
                    return $transliterationEnabled;
                }

                if ('validate_page_hit_required_data' === $parameter) {
                    return $validatePageHitRequiredData;
                }
            });
        $validatorMock               = $this->createMock(ValidatorInterface::class);

        $validatorMock->method('validate')
            ->willReturn(new ConstraintViolationList());

        return new PageModel(
            $this->createStub(CookieHelper::class),
            $this->ipLookupHelper,
            $this->createStub(LeadModel::class),
            $this->createStub(FieldModel::class),
            $redirectModel,
            $this->createStub(TrackableModel::class),
            $this->createStub(MessageBus::class),
            $this->companyModel,
            $this->createStub(DeviceTracker::class),
            $contactTracker,
            $coreParametersHelper,
            $this->contactRequestHelper,
            $this->createStub(VariantConverterService::class),
            $entityManager,
            $this->security = $this->createMock(CorePermissions::class),
            $this->createStub(EventDispatcher::class),
            $this->router,
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(StatRepository::class),
            $this->createStub(BotRatioHelper::class),
            $validatorMock,
            $this->createStub(PageRepository::class), // $pageRepository
            $this->createStub(HitRepository::class), // $hitRepository
            $this->createStub(EmailRepository::class), // $emailRepository
            $this->createStub(UtmTagRepository::class), // $utmTagRepository
            $this->createStub(RedirectRepository::class),
            $this->createStub(TrackableRepository::class),
            $this->createStub(LeadRepository::class),
            $this->createStub(CompanyLeadRepository::class)
        );
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel(): MockObject
    {
        $mockRedirectModel = $this->getMockBuilder(RedirectModel::class)
            ->setConstructorArgs([
                $this->createStub(EntityManagerInterface::class),
                $this->createStub(CorePermissions::class),
                $this->createStub(EventDispatcherInterface::class),
                $this->createStub(UrlGeneratorInterface::class),
                $this->createStub(Translator::class),
                $this->createStub(UserHelper::class),
                $this->createStub(LoggerInterface::class),
                $this->createStub(CoreParametersHelper::class),
            ])
            ->onlyMethods(['createRedirectEntity', 'generateRedirectUrl'])
            ->getMock();

        $mockRedirectModel->autowireRedirectModel($this->createMock(Shortener::class), $this->createStub(RedirectRepository::class));

        $mockRedirectModel
            ->method('createRedirectEntity')
            ->willReturn($this->createStub(Redirect::class));

        $mockRedirectModel
            ->method('generateRedirectUrl')
            ->willReturn('http://some-url.com');

        return $mockRedirectModel;
    }
}
