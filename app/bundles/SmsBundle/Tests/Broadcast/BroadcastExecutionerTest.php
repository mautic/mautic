<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Broadcast;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\SmsBundle\Broadcast\BroadcastExecutioner;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Model\SmsModel;
use Mautic\SmsBundle\Sms\TransportChain;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BroadcastExecutionerTest extends MauticMysqlTestCase
{
    public function testLegacyServiceIdAliasesCanonicalExecutionerWithMauticLogger(): void
    {
        $canonicalExecutioner = self::getContainer()->get(BroadcastExecutioner::class);

        $legacyExecutioner = self::getContainer()->get('mautic.sms.broadcast.executioner'); // @phpstan-ignore mautic.noContainerGet
        $mauticLogger      = self::getContainer()->get('monolog.logger.mautic'); // @phpstan-ignore mautic.noContainerGet

        $this->assertSame($canonicalExecutioner, $legacyExecutioner);
        $this->assertSame(
            $mauticLogger,
            (new \ReflectionProperty(BroadcastExecutioner::class, 'logger'))->getValue($canonicalExecutioner),
        );
    }

    public function testSendNextBatchUsesOneModelCallAndReturnsAuthoritativeCounts(): void
    {
        [$sms, $contacts, $segment] = $this->createBroadcast(3, 'single-batch');
        $batches                    = [];
        $loadedContacts             = [];
        $executioner                = $this->createExecutioner(
            $this->createSuccessfulSmsModel($batches, $loadedContacts),
            $this->createStub(LoggerInterface::class),
        );
        $partition = new ContactLimiter(50);

        $result = $executioner->sendNextBatch($sms, 2, $partition);

        $expectedContactIds = [$contacts[0]->getId(), $contacts[1]->getId()];
        $this->assertSame([
            [
                'contactIds' => $expectedContactIds,
                'options'    => [
                    'channel' => ['sms', $sms->getId()],
                    'listId'  => [
                        $contacts[0]->getId() => $segment->getId(),
                        $contacts[1]->getId() => $segment->getId(),
                    ],
                ],
            ],
        ], $batches);
        $this->assertSame(2, $result->getProcessedCount());
        $this->assertSame(2, $result->getSubmittedCount());
        $this->assertSame(0, $result->getScheduledCount());
        $this->assertSame(0, $result->getFailedCount());
        $this->assertSame(1, $result->getRemainingCount());
        $this->assertSame($contacts[1]->getId() + 1, $partition->getMinContactId());

        foreach ($loadedContacts as $loadedContact) {
            $this->assertFalse($this->em->contains($loadedContact));
        }
    }

    public function testExecuteClampsAContactLimitSmallerThanTheBatch(): void
    {
        [$sms, $contacts] = $this->createBroadcast(4, 'exact-limit');
        $batches          = [];
        $loadedContacts   = [];
        $executioner      = $this->createExecutioner(
            $this->createSuccessfulSmsModel($batches, $loadedContacts),
            $this->createStub(LoggerInterface::class),
        );
        $event = new ChannelBroadcastEvent('sms', $sms->getId(), new BufferedOutput());
        $event->setBatch(3);
        $event->setLimit(2);

        $executioner->execute($event);

        $this->assertSame([[$contacts[0]->getId(), $contacts[1]->getId()]], array_column($batches, 'contactIds'));
        $this->assertSame([
            'success'                => 2,
            'failed'                 => 0,
            'failedRecipientsByList' => [],
        ], array_values($event->getResults())[0]);
    }

    public function testExecuteAdvancesTheCursorAcrossBatchesAndPreservesThreadPartitions(): void
    {
        [$sms, $contacts] = $this->createBroadcast(6, 'cursor-and-partitions');
        $batches          = [];
        $loadedContacts   = [];
        $executioner      = $this->createExecutioner(
            $this->createSuccessfulSmsModel($batches, $loadedContacts),
            $this->createStub(LoggerInterface::class),
        );
        $event = new ChannelBroadcastEvent('sms', $sms->getId(), new BufferedOutput());
        $event->setBatch(2);
        $event->setLimit(6);
        $event->setThreadId(1);
        $event->setMaxThreads(2);

        $executioner->execute($event);

        $processedIds = array_merge(...array_column($batches, 'contactIds'));
        $expectedIds  = array_values(array_map(
            static fn (Lead $contact): int => $contact->getId(),
            array_filter($contacts, static fn (Lead $contact): bool => 0 === $contact->getId() % 2),
        ));
        $this->assertSame($expectedIds, $processedIds);
        $this->assertSame($processedIds, array_values(array_unique($processedIds)));
        $this->assertGreaterThan(1, count($batches));
    }

    public function testExecuteReportsOneSanitizedFailureAndKeepsPriorBatchCounts(): void
    {
        [$sms]    = $this->createBroadcast(2, 'safe-exception');
        $call     = 0;
        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->method('sendSms')
            ->willReturnCallback(static function (Sms $sentSms, array $contactIds) use (&$call): array {
                ++$call;
                if (2 === $call) {
                    throw new \RuntimeException('+41791234567 private message body');
                }

                return [
                    $contactIds[0] => [
                        'sent'   => true,
                        'status' => 'mautic.sms.timeline.status.delivered',
                    ],
                ];
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS broadcast execution failed.', [
                'smsId'          => $sms->getId(),
                'exceptionClass' => \RuntimeException::class,
            ]);
        $executioner = $this->createExecutioner($smsModel, $logger);
        $event       = new ChannelBroadcastEvent('sms', $sms->getId(), new BufferedOutput());
        $event->setBatch(1);
        $event->setLimit(2);

        $executioner->execute($event);

        $this->assertSame([
            'success'                => 1,
            'failed'                 => 1,
            'failedRecipientsByList' => [],
        ], array_values($event->getResults())[0]);
    }

    public function testMixedTranslationFailureKeepsCompletedOutcomesAndLeavesFailedCollectionRetryable(): void
    {
        [$sms, $contacts] = $this->createBroadcast(2, 'partial-translations');
        $sms->setLanguage('en');

        $translatedSms = new Sms();
        $translatedSms->setName('partial-translations-fr');
        $translatedSms->setMessage('Bonjour');
        $translatedSms->setLanguage('fr_FR');
        $translatedSms->setIsPublished(true);
        $translatedSms->setTranslationParent($sms);
        $contacts[1]->addUpdatedField('preferred_locale', 'fr_FR');
        $this->em->persist($translatedSms);
        $this->em->flush();

        $smsId           = $sms->getId();
        $translatedSmsId = $translatedSms->getId();
        $firstContactId  = $contacts[0]->getId();
        $secondContactId = $contacts[1]->getId();
        $this->em->clear();

        $sms = $this->em->find(Sms::class, $smsId);
        $localizedContact = $this->em->find(Lead::class, $secondContactId);
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertInstanceOf(Lead::class, $localizedContact);
        $localizedContact->addUpdatedField('preferred_locale', 'fr_FR');

        $transport = $this->createMock(TransportChain::class);
        $transport->expects($this->exactly(2))
            ->method('sendBatchSms')
            ->willReturnCallback(static function (RecipientCollection $collection, string $message): RecipientCollection {
                if ('Bonjour' === $message) {
                    throw new \RuntimeException('Provider rejected +41790000001: private message body');
                }

                foreach ($collection as $recipient) {
                    $recipient->setResult(true);
                }

                return $collection;
            });
        self::getContainer()->set('mautic.sms.transport_chain', $transport);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS broadcast execution failed.', [
                'smsId'          => $smsId,
                'exceptionClass' => \RuntimeException::class,
            ]);
        $smsModel = self::getContainer()->get(SmsModel::class);
        ReflectionHelper::setValue($smsModel, 'transport', $transport);
        $executioner = $this->createExecutioner($smsModel, $logger);

        $result = $executioner->sendNextBatch($sms, 2, new ContactLimiter(2));

        $this->assertSame(1, $result->getProcessedCount());
        $this->assertSame(1, $result->getSubmittedCount());
        $this->assertSame(1, $result->getExecutionFailureCount());
        $this->assertSame(1, $result->getFailedCount());
        $this->assertSame([], $result->getFailedContacts());
        $this->assertSame(1, $result->getRemainingCount());

        $firstStats = $smsModel->getStatRepository()->findBy(['lead' => $firstContactId]);
        $this->assertCount(1, $firstStats);
        $this->assertFalse($firstStats[0]->isFailed());
        $this->assertSame($smsId, $firstStats[0]->getSms()->getId());
        $this->assertSame([], $smsModel->getStatRepository()->findBy(['lead' => $secondContactId]));

        $this->em->clear();
        $sms           = $this->em->find(Sms::class, $smsId);
        $translatedSms = $this->em->find(Sms::class, $translatedSmsId);
        $this->assertInstanceOf(Sms::class, $sms);
        $this->assertInstanceOf(Sms::class, $translatedSms);
        $this->assertSame(1, $sms->getSentCount());
        $this->assertSame(0, $translatedSms->getSentCount());

        $pendingContacts = self::getContainer()->get(BroadcastQuery::class)
            ->getPendingContacts($sms, new ContactLimiter(10));
        $this->assertSame([$secondContactId], array_map(
            static fn (array $contact): int => (int) $contact['id'],
            $pendingContacts,
        ));
    }

    public function testExecuteRetainsSentBatchOutcomeWhenRemainingCountFails(): void
    {
        [$sms]         = $this->createBroadcast(2, 'post-send-failure');
        $broadcastQuery = self::getContainer()->get(BroadcastQuery::class);
        $failingEntityManager = $this->createStub(EntityManagerInterface::class);
        $failingEntityManager->method('getConnection')
            ->willThrowException(new \RuntimeException('+41791234567 private message body'));

        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->expects($this->once())
            ->method('sendSms')
            ->willReturnCallback(function (Sms $sentSms, array $contactIds) use ($broadcastQuery, $failingEntityManager): array {
                ReflectionHelper::setValue($broadcastQuery, 'entityManager', $failingEntityManager);

                return [
                    $contactIds[0] => [
                        'sent'   => true,
                        'status' => 'mautic.sms.timeline.status.delivered',
                    ],
                ];
            });
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS broadcast execution failed.', [
                'smsId'          => $sms->getId(),
                'exceptionClass' => \RuntimeException::class,
            ]);
        $executioner = $this->createExecutioner($smsModel, $logger);
        $event       = new ChannelBroadcastEvent('sms', $sms->getId(), new BufferedOutput());
        $event->setBatch(1);
        $event->setLimit(2);

        try {
            $executioner->execute($event);
        } finally {
            ReflectionHelper::setValue($broadcastQuery, 'entityManager', $this->em);
        }

        $this->assertSame([
            'success'                => 1,
            'failed'                 => 1,
            'failedRecipientsByList' => [],
        ], array_values($event->getResults())[0]);
    }

    public function testSendNextBatchRetainsOutcomeAndRemainingCountWhenDetachFails(): void
    {
        [$sms]   = $this->createBroadcast(2, 'post-send-detach-failure');
        $call     = 0;
        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->expects($this->once())
            ->method('sendSms')
            ->willReturnCallback(static function (Sms $sentSms, array $contactIds) use (&$call): array {
                ++$call;

                return [
                    $contactIds[0] => [
                        'sent'   => true,
                        'status' => 'mautic.sms.timeline.status.delivered',
                    ],
                ];
            });
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadRepository->expects($this->once())
            ->method('detachEntities')
            ->willThrowException(new \RuntimeException('+41791234567 private message body'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS broadcast execution failed.', [
                'smsId'          => $sms->getId(),
                'exceptionClass' => \RuntimeException::class,
            ]);
        $executioner = $this->createExecutioner($smsModel, $logger, $leadRepository);
        $result      = $executioner->sendNextBatch($sms, 1, new ContactLimiter(1));

        $this->assertSame(1, $call);
        $this->assertSame(1, $result->getProcessedCount());
        $this->assertSame(1, $result->getSubmittedCount());
        $this->assertSame(1, $result->getExecutionFailureCount());
        $this->assertSame(1, $result->getFailedCount());
        $this->assertSame(1, $result->getRemainingCount());
    }

    public function testDetachFailureDoesNotSuppressOrDoubleLogSendFailure(): void
    {
        [$sms]   = $this->createBroadcast(1, 'send-and-detach-failure');
        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->expects($this->once())
            ->method('sendSms')
            ->willThrowException(new \DomainException('+41791111111 primary private message'));
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadRepository->expects($this->once())
            ->method('detachEntities')
            ->willThrowException(new \RuntimeException('+41792222222 secondary private message'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('SMS broadcast execution failed.', [
                'smsId'          => $sms->getId(),
                'exceptionClass' => \DomainException::class,
            ]);
        $executioner = $this->createExecutioner($smsModel, $logger, $leadRepository);
        $event       = new ChannelBroadcastEvent('sms', $sms->getId(), new BufferedOutput());
        $event->setBatch(1);
        $event->setLimit(1);

        $executioner->execute($event);

        $this->assertSame([
            'success'                => 0,
            'failed'                 => 1,
            'failedRecipientsByList' => [],
        ], array_values($event->getResults())[0]);
    }

    public function testSendNextBatchRechecksTheSmsTypeAndPublication(): void
    {
        [$templateSms] = $this->createBroadcast(1, 'state-recheck-template');
        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->expects($this->never())->method('sendSms');
        $executioner = $this->createExecutioner($smsModel, $this->createStub(LoggerInterface::class));

        $templateSms->setSmsType('template');
        $this->em->persist($templateSms);
        $this->em->flush();
        $templateResult = $executioner->sendNextBatch($templateSms, 1);

        [$unpublishedSms] = $this->createBroadcast(1, 'state-recheck-unpublished');
        $unpublishedSms->setIsPublished(false);
        $this->em->persist($unpublishedSms);
        $this->em->flush();
        $unpublishedResult = $executioner->sendNextBatch($unpublishedSms, 1);

        $this->assertSame(0, $templateResult->getProcessedCount());
        $this->assertSame(0, $unpublishedResult->getProcessedCount());
    }

    private function createExecutioner(
        SmsModel $smsModel,
        LoggerInterface $logger,
        ?LeadRepository $leadRepository = null,
    ): BroadcastExecutioner {
        $leadRepository ??= $this->em->getRepository(Lead::class);
        $smsRepository  = $this->em->getRepository(Sms::class);
        $this->assertInstanceOf(LeadRepository::class, $leadRepository);
        $this->assertInstanceOf(SmsRepository::class, $smsRepository);

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('SMS');

        return new BroadcastExecutioner(
            $smsModel,
            self::getContainer()->get(BroadcastQuery::class),
            $translator,
            $leadRepository,
            $smsRepository,
            $this->em,
            $logger,
        );
    }

    /**
     * @param array<int, array{contactIds: int[], options: array<string, mixed>}> $batches
     * @param Lead[]                                                              $loadedContacts
     */
    private function createSuccessfulSmsModel(array &$batches, array &$loadedContacts): SmsModel
    {
        $smsModel = $this->createMock(SmsModel::class);
        $smsModel->method('sendSms')
            ->willReturnCallback(function (Sms $sms, array $contactIds, array $options, array &$contacts) use (&$batches, &$loadedContacts): array {
                $batches[] = [
                    'contactIds' => $contactIds,
                    'options'    => $options,
                ];
                $results = [];

                foreach ($contactIds as $contactId) {
                    $contact = $this->em->find(Lead::class, $contactId);
                    $this->assertInstanceOf(Lead::class, $contact);
                    $contacts[$contactId] = $contact;
                    $loadedContacts[]      = $contact;
                    $results[$contactId]   = [
                        'sent'   => true,
                        'status' => 'mautic.sms.timeline.status.delivered',
                    ];
                }

                return $results;
            });

        return $smsModel;
    }

    /**
     * @return array{Sms, Lead[], LeadList}
     */
    private function createBroadcast(int $contactCount, string $alias): array
    {
        $segment = new LeadList();
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setAlias($alias);
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $contacts = [];
        for ($i = 0; $i < $contactCount; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Broadcast');
            $contact->setLastname('Recipient '.$i);
            $contact->setMobile('+41790000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT));
            $this->em->persist($contact);

            $membership = new ListLead();
            $membership->setLead($contact);
            $membership->setList($segment);
            $membership->setDateAdded(new \DateTime());
            $this->em->persist($membership);
            $contacts[] = $contact;
        }

        $sms = new Sms();
        $sms->setName($alias);
        $sms->setMessage('Broadcast message');
        $sms->setSmsType('list');
        $sms->setIsPublished(true);
        $sms->setPublishUp(new \DateTime('-1 minute'));
        $sms->addList($segment);
        $this->em->persist($sms);
        $this->em->flush();

        return [$sms, $contacts, $segment];
    }
}
