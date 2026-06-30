<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Entity\StatRepository;
use Mautic\SmsBundle\Form\Type\SmsType;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SmsModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&CacheStorageHelper
     */
    private \PHPUnit\Framework\MockObject\Stub $cacheStorageHelper;

    private MockObject&EntityManagerInterface $entityManger;

    private \PHPUnit\Framework\MockObject\Stub&LeadModel $leadModel;

    private \PHPUnit\Framework\MockObject\Stub&TrackableModel $pageTrackableModel;

    private MockObject&TransportChain $transport;

    private MockObject&CorePermissions $security;

    private MockObject&EventDispatcherInterface $dispatcher;

    private \PHPUnit\Framework\MockObject\Stub&UrlGeneratorInterface $urlGenerator;

    private MockObject&TranslatorInterface $translator;

    private \PHPUnit\Framework\MockObject\Stub&UserHelper $userHelper;

    private \PHPUnit\Framework\MockObject\Stub&LoggerInterface $logger;

    private \PHPUnit\Framework\MockObject\Stub&CoreParametersHelper $coreParametersHelper;

    private SmsModel $smsModel;

    protected function setUp(): void
    {
        $this->pageTrackableModel   = $this->createStub(TrackableModel::class);
        $this->leadModel            = $this->createStub(LeadModel::class);
        $this->transport            = $this->createMock(TransportChain::class);
        /** @phpstan-ignore classConstant.deprecatedClass */
        $this->cacheStorageHelper   = $this->createStub(CacheStorageHelper::class);
        $this->entityManger         = $this->createMock(EntityManagerInterface::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->urlGenerator         = $this->createStub(UrlGeneratorInterface::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->userHelper           = $this->createStub(UserHelper::class);
        $this->logger               = $this->createStub(LoggerInterface::class);
        $this->coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $this->dispatcher->method('dispatch')
            ->willReturnArgument(0);
        $this->smsModel             = new SmsModel(
            $this->pageTrackableModel,
            $this->leadModel,
            $this->transport,
            $this->cacheStorageHelper,
            $this->entityManger,
            $this->security,
            $this->dispatcher,
            $this->urlGenerator,
            $this->translator,
            $this->userHelper,
            $this->logger,
            $this->coreParametersHelper
        );
    }

    /**
     * Test to get lookup results when class name is sent as a parameter.
     */
    public function testGetLookupResultsWhenTypeIsClass(): void
    {
        $entities = [['name' => 'Mautic', 'id' => 1, 'language' => 'cs'], ['name' => 'Mautic MMS', 'id' => 2, 'media' => ['test.jpg'], 'language' => 'cs']];

        /** @var MockObject|SmsRepository $repositoryMock */
        $repositoryMock = $this->createMock(SmsRepository::class);
        $repositoryMock->method('getSmsList')
            ->with('', 10, 0, true, false)
            ->willReturn($entities);

        $this->entityManger->method('getRepository')
            ->with(Sms::class)
            ->willReturn($repositoryMock);

        $this->security->method('isGranted')
            ->with('sms:smses:viewother')
            ->willReturn(true);

        $this->translator
            ->method('trans')
            ->with('mautic.sms.form.mms')
            ->willReturn('MMS');

        $textMessages = $this->smsModel->getLookupResults(SmsType::class);
        $this->assertSame('Mautic', $textMessages['cs'][1], 'Mautic is the right text message name');
        $this->assertSame('[MMS] Mautic MMS', $textMessages['cs'][2], 'Mautic is the right text message name');
    }

    public function testSendSmsNotPublished(): void
    {
        $sms = new Sms();
        $sms->setIsPublished(false);
        $lead = new Lead();
        $lead->setId(1);
        $results = $this->smsModel->sendSms($sms, $lead);
        self::assertFalse((bool) $results[1]['sent']);
        self::assertSame('mautic.sms.campaign.failed.unpublished', $results[1]['status']);
    }

    public function testSendSMSTest(): void
    {
        $this->sendMessage();
    }

    public function testSendMMSTest(): void
    {
        $this->sendMessage(true);
    }

    private function sendMessage(bool $isMMS = false): void
    {
        $repositoryMock     = $this->createMock(SmsRepository::class);
        $statRepositoryMock = $this->createMock(StatRepository::class);

        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('test');
        if ($isMMS) {
            $sms->setMedia(['test,png']);
        }

        $lead1 = new Lead();
        $lead1->setMobile('+1234567890');
        $lead1->setId(1);

        $lead2 = new Lead();
        $lead2->setMobile('+123456790');
        $lead2->setId(2);

        // Partial mock, mocks just getRepository
        $smsModel = $this->getMockBuilder(SmsModel::class)
            ->setConstructorArgs([
                $this->pageTrackableModel,
                $this->leadModel,
                $this->transport,
                $this->cacheStorageHelper,
                $this->entityManger,
                $this->security,
                $this->dispatcher,
                $this->urlGenerator,
                $this->translator,
                $this->userHelper,
                $this->logger,
                $this->coreParametersHelper,
            ])
            ->onlyMethods(['getRepository', 'getStatRepository'])
            ->getMock();
        $smsModel->method('getRepository')
            ->willReturn($smsRepo = $this->createMock(SmsRepository::class));

        $smsModel->method('getStatRepository')
            ->willReturn($this->createStub(StatRepository::class));

        $smsRepo->expects($this->once())
            ->method('upCount')
            ->with($sms->getId(), 'sent', 2);

        $smsModel->method('getRepository')
            ->willReturn($repositoryMock);

        $smsModel->method('getStatRepository')
            ->willReturn($statRepositoryMock);

        if ($isMMS) {
            $this->transport->expects($this->once())
                ->method('sendMMS')
                ->willReturnCallback(fn (RecipientCollection $recipientCollection) => $this->setRecipientResult($recipientCollection));
        } else {
            $this->transport->expects($this->once())
                ->method('sendBatchSms')
                ->willReturnCallback(fn (RecipientCollection $recipientCollection) => $this->setRecipientResult($recipientCollection));
        }

        $results = $smsModel->sendSms($sms, [$lead1, $lead2], ['channel' => ['campaign.event', 1]]);
        $this->assertCount(2, $results);
    }

    /**
     * @param RecipientCollection<SmsRecipientDTO> $recipientCollection
     *
     * @return RecipientCollection<SmsRecipientDTO>
     */
    private function setRecipientResult(RecipientCollection $recipientCollection): RecipientCollection
    {
        /** @var SmsRecipientDTO $recipient */
        foreach ($recipientCollection as $recipient) {
            $recipient->setResult(true);
        }

        return $recipientCollection;
    }
}
