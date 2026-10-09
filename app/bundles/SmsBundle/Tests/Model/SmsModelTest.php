<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Entity\StatRepository;
use Mautic\SmsBundle\Event\FilterEvent;
use Mautic\SmsBundle\Exception\PrimaryTransportNotEnabledException;
use Mautic\SmsBundle\Form\Type\SmsType;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SmsModelTest extends \PHPUnit\Framework\TestCase
{
    private \PHPUnit\Framework\MockObject\Stub&LeadModel $leadModel;

    private MockObject&TransportChain $transport;

    private MockObject&CorePermissions $security;

    private MockObject&TranslatorInterface $translator;

    private MockObject&LoggerInterface $logger;

    private MockObject&SmsRepository $smsRepository;

    private MockObject&StatRepository $statRepository;

    private SmsModel $smsModel;

    /**
     * @var int[]
     */
    private array $thirdPartyFilteredContactIds = [];

    protected function setUp(): void
    {
        $pageTrackableModel         = $this->createStub(TrackableModel::class);
        $this->leadModel            = $this->createStub(LeadModel::class);
        $this->transport            = $this->createMock(TransportChain::class);
        $entityManger               = $this->createStub(EntityManagerInterface::class);
        $this->security             = $this->createMock(CorePermissions::class);
        $dispatcher                 = $this->createMock(EventDispatcherInterface::class);
        $urlGenerator               = $this->createStub(UrlGeneratorInterface::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $userHelper                 = $this->createStub(UserHelper::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
        $coreParametersHelper       = $this->createStub(CoreParametersHelper::class);
        $this->smsRepository        = $this->createMock(SmsRepository::class);
        $this->statRepository       = $this->createMock(StatRepository::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(function (object $event): object {
                if ($event instanceof FilterEvent) {
                    $contactsWithoutNumbers = array_filter(
                        $event->getContacts(),
                        static fn (Lead $contact): bool => '' === trim((string) $contact->getLeadPhoneNumber()),
                    );
                    $event->removeContacts(array_map(
                        static fn (Lead $contact): int => $contact->getId(),
                        $contactsWithoutNumbers,
                    ), FilterEvent::REMOVAL_REASON_MISSING_NUMBER);
                    $event->removeContacts($this->thirdPartyFilteredContactIds);
                }

                return $event;
            });
        $this->smsModel             = new SmsModel(
            $pageTrackableModel,
            $this->leadModel,
            $this->transport,
            $this->createStub(CacheStorageHelper::class),
            $entityManger,
            $this->security,
            $dispatcher,
            $urlGenerator,
            $this->translator,
            $userHelper,
            $this->logger,
            $coreParametersHelper,
            $this->smsRepository,
            $this->statRepository,
            $this->createStub(DoNotContactRepository::class),
        );
    }

    /**
     * Test to get lookup results when class name is sent as a parameter.
     */
    public function testGetLookupResultsWhenTypeIsClass(): void
    {
        $entities = [['name' => 'Mautic', 'id' => 1, 'language' => 'cs'], ['name' => 'Mautic MMS', 'id' => 2, 'media' => ['test.jpg'], 'language' => 'cs']];

        $this->smsRepository->method('getSmsList')
            ->with('', 10, 0, true, null)
            ->willReturn($entities);

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
        $this->assertFalse((bool) $results[1]['sent']);
        $this->assertSame('mautic.sms.campaign.failed.unpublished', $results[1]['status']);
    }

    public function testSendSMSTest(): void
    {
        $this->sendMessage();
    }

    public function testSendMMSTest(): void
    {
        $this->sendMessage(true);
    }

    public function testSendSmsPersistsAWhitespaceOnlyNumberAsAFailedStat(): void
    {
        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('test');

        $lead = new Lead();
        $lead->setId(13);
        $lead->setMobile('   ');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.sms.campaign.failed.missing_number')
            ->willReturn('Missing phone number for contact.');
        $this->transport->expects($this->never())->method('sendBatchSms');
        $this->smsRepository->expects($this->never())->method('upCount');
        $this->statRepository->expects($this->once())
            ->method('saveEntities')
            ->willReturnCallback(function (array $stats) use ($lead, $sms): void {
                $this->assertSame([13], array_keys($stats));
                $this->assertSame($lead, $stats[13]->getLead());
                $this->assertSame($sms, $stats[13]->getSms());
                $this->assertTrue($stats[13]->isFailed());
                $this->assertSame(['failed' => ['Missing phone number for contact.']], $stats[13]->getDetails());
            });

        $loadedContacts = [];
        $results        = $this->smsModel->sendSms($sms, $lead, [], $loadedContacts);

        $this->assertSame([13 => $lead], $loadedContacts);
        $this->assertSame(
            [13 => ['sent' => false, 'status' => 'mautic.sms.campaign.failed.missing_number']],
            $results,
        );
    }

    public function testSendSmsDoesNotPersistContactsRemovedByAThirdPartyFilter(): void
    {
        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('test');

        $lead = new Lead();
        $lead->setId(13);
        $lead->setMobile('+41790000000');
        $this->thirdPartyFilteredContactIds = [13];

        $this->translator->expects($this->never())->method('trans');
        $this->transport->expects($this->never())->method('sendBatchSms');
        $this->smsRepository->expects($this->never())->method('upCount');
        $this->statRepository->expects($this->never())->method('saveEntities');

        $loadedContacts = [];
        $results        = $this->smsModel->sendSms($sms, $lead, [], $loadedContacts);

        $this->assertSame([13 => $lead], $loadedContacts);
        $this->assertSame(
            [13 => ['sent' => false, 'status' => 'mautic.sms.campaign.failed.missing_number']],
            $results,
        );
    }

    /**
     * @param int|array<int, int> $listIds
     */
    #[DataProvider('listIdProvider')]
    public function testSendSmsSupportsListIdsAndPersistsImmediateRejections(int|array $listIds, int $expectedRejectedListId): void
    {
        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('test');

        $acceptedLead = new Lead();
        $acceptedLead->setId(17);
        $acceptedLead->setMobile('+1234567890');
        $rejectedLead = new Lead();
        $rejectedLead->setId(42);
        $rejectedLead->setMobile('+1234567891');

        $acceptedList = new LeadList();
        $rejectedList = new LeadList();
        $expectedRejectedList = 100 === $expectedRejectedListId ? $acceptedList : $rejectedList;
        $listRepository = $this->createStub(LeadListRepository::class);
        $listRepository->method('getEntity')
            ->willReturnCallback(static fn (int $id): LeadList => match ($id) {
                100 => $acceptedList,
                200 => $rejectedList,
                default => throw new \UnexpectedValueException("Unexpected list ID {$id}"),
            });
        $this->leadModel->method('getLeadListRepository')->willReturn($listRepository);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.sms.timeline.status.failed')
            ->willReturn('Text Message Failed');
        $this->transport->expects($this->once())
            ->method('sendBatchSms')
            ->willReturnCallback(static function (RecipientCollection $recipients): RecipientCollection {
                foreach ($recipients as $recipient) {
                    $recipient->setResult(17 === $recipient->getKey());
                }

                return $recipients;
            });
        $this->smsRepository->expects($this->once())
            ->method('upCount')
            ->with(1, 'sent', 1);
        $this->statRepository->expects($this->once())
            ->method('saveEntities')
            ->willReturnCallback(function (array $stats) use ($acceptedList, $expectedRejectedList): void {
                $this->assertSame([17, 42], array_keys($stats));
                $this->assertSame($acceptedList, $stats[17]->getList());
                $this->assertFalse($stats[17]->isFailed());
                $this->assertSame($expectedRejectedList, $stats[42]->getList());
                $this->assertTrue($stats[42]->isFailed());
                $this->assertSame(['failed' => ['Text Message Failed']], $stats[42]->getDetails());
            });

        $loadedContacts = [];
        $results        = $this->smsModel->sendSms(
            $sms,
            [$acceptedLead, $rejectedLead],
            ['listId' => $listIds],
            $loadedContacts,
        );

        $this->assertSame([17 => $acceptedLead, 42 => $rejectedLead], $loadedContacts);
        $this->assertTrue($results[17]['sent']);
        $this->assertSame('mautic.sms.timeline.status.delivered', $results[17]['status']);
        $this->assertFalse($results[42]['sent']);
        $this->assertSame('mautic.sms.timeline.status.failed', $results[42]['status']);
    }

    /**
     * @return iterable<string, array{int|array<int, int>, int}>
     */
    public static function listIdProvider(): iterable
    {
        yield 'legacy scalar list ID' => [100, 100];
        yield 'per-contact list ID map' => [[17 => 100, 42 => 200], 200];
    }

    public function testPrimaryTransportFailureIsReportedWithoutLoggingExceptionDetails(): void
    {
        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('private message body');

        $lead = new Lead();
        $lead->setId(13);
        $lead->setMobile('+41790000000');

        $this->transport->expects($this->once())
            ->method('sendBatchSms')
            ->willThrowException(new PrimaryTransportNotEnabledException('Failed for +41790000000: private message body'));
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Primary SMS transport is not enabled.');
        $this->smsRepository->expects($this->never())->method('upCount');
        $this->statRepository->expects($this->never())->method('saveEntities');

        $results = $this->smsModel->sendSms($sms, $lead);

        $this->assertSame(
            [13 => ['sent' => false, 'status' => 'mautic.sms.config.no_transport']],
            $results,
        );
    }

    public function testProviderErrorStringIsPersistedAndReportedAsABoundedFailure(): void
    {
        $sms = new Sms();
        ReflectionHelper::setValue($sms, 'id', 1);
        $sms->setMessage('private message body');

        $lead = new Lead();
        $lead->setId(13);
        $lead->setMobile('+41790000000');

        $providerError = 'Provider rejected +41790000000: private message body';
        $this->transport->expects($this->once())
            ->method('sendBatchSms')
            ->willReturnCallback(static function (RecipientCollection $recipients) use ($providerError): RecipientCollection {
                foreach ($recipients as $recipient) {
                    $recipient->setResult($providerError);
                }

                return $recipients;
            });
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.sms.timeline.status.failed')
            ->willReturn('Text Message Failed');
        $this->smsRepository->expects($this->never())->method('upCount');
        $this->statRepository->expects($this->once())
            ->method('saveEntities')
            ->willReturnCallback(function (array $stats) use ($providerError): void {
                $this->assertTrue($stats[13]->isFailed());
                $this->assertSame(['failed' => ['Text Message Failed']], $stats[13]->getDetails());
                $this->assertStringNotContainsString($providerError, json_encode($stats[13]->getDetails(), JSON_THROW_ON_ERROR));
            });

        $results = $this->smsModel->sendSms($sms, $lead);

        $this->assertFalse($results[13]['sent']);
        $this->assertSame('mautic.sms.timeline.status.failed', $results[13]['status']);
        $this->assertStringNotContainsString($providerError, json_encode($results, JSON_THROW_ON_ERROR));
    }

    private function sendMessage(bool $isMMS = false): void
    {
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

        $this->smsRepository->expects($this->once())
            ->method('upCount')
            ->with($sms->getId(), 'sent', 2);

        if ($isMMS) {
            $this->transport->expects($this->once())
                ->method('sendMMS')
                ->willReturnCallback(fn (RecipientCollection $recipientCollection): RecipientCollection => $this->setRecipientResult($recipientCollection));
        } else {
            $this->transport->expects($this->once())
                ->method('sendBatchSms')
                ->willReturnCallback(fn (RecipientCollection $recipientCollection): RecipientCollection => $this->setRecipientResult($recipientCollection));
        }

        $results = $this->smsModel->sendSms($sms, [$lead1, $lead2], ['channel' => ['campaign.event', 1]]);
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
