<?php

namespace Mautic\SmsBundle\Broadcast;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Exception\PartialBatchException;
use Mautic\SmsBundle\Model\SmsModel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class BroadcastExecutioner
{
    public function __construct(
        private SmsModel $smsModel,
        private BroadcastQuery $broadcastQuery,
        private TranslatorInterface $translator,
        private LeadRepository $leadRepository,
        private SmsRepository $smsRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(ChannelBroadcastEvent $event): void
    {
        // Get list of published broadcasts or broadcast if there is only a single ID
        $smses = $this->smsRepository->getPublishedBroadcastsIterable($event->getId());
        foreach ($smses as $sms) {
            $partition = new ContactLimiter(
                $event->getBatch(),
                null,
                $event->getMinContactIdFilter(),
                $event->getMaxContactIdFilter(),
                [],
                $event->getThreadId(),
                $event->getMaxThreads(),
            );
            $result         = new BroadcastResult();
            $remainingLimit = max(0, $event->getLimit());

            try {
                while ($remainingLimit > 0) {
                    $batchSize   = min($event->getBatch(), $remainingLimit);
                    $batch       = $this->processNextBatch($sms, $batchSize, $partition, false);
                    $batchResult = $batch['result'];
                    $result->merge($batchResult);

                    $processedCount  = $batchResult->getProcessedCount();
                    $remainingLimit -= $processedCount;

                    if ($batchResult->getExecutionFailureCount() > 0 || !$batch['hasMore']) {
                        break;
                    }
                }
            } catch (\Throwable $exception) {
                $result->executionFailed();
                $this->reportExecutionFailure($sms, $exception);
            }

            $event->setResults(
                sprintf('%s: %s', $this->translator->trans('mautic.sms.sms'), $sms->getName()),
                $result->getSuccessfulCount(),
                $result->getFailedCount()
            );
        }
    }

    public function sendNextBatch(Sms $sms, int $batchSize, ?ContactLimiter $partition = null): BroadcastResult
    {
        return $this->processNextBatch($sms, $batchSize, $partition, true)['result'];
    }

    /**
     * @return array{result: BroadcastResult, hasMore: bool}
     */
    private function processNextBatch(Sms $sms, int $batchSize, ?ContactLimiter $partition, bool $countRemaining): array
    {
        $result = new BroadcastResult();
        if ($batchSize <= 0 || null === $sms->getId()) {
            return ['result' => $result, 'hasMore' => false];
        }

        $this->entityManager->refresh($sms);
        if ('list' !== $sms->getSmsType() || !$sms->isPublished()) {
            return ['result' => $result, 'hasMore' => false];
        }

        $partition ??= new ContactLimiter($batchSize);
        $contacts = $this->broadcastQuery->getPendingContacts($sms, $partition, $batchSize);
        if ([] === $contacts) {
            if ($countRemaining) {
                $result->setRemainingCount($this->broadcastQuery->getPendingCount($sms, $partition));
            }

            return ['result' => $result, 'hasMore' => false];
        }

        $contactIds = [];
        $listIds    = [];
        foreach ($contacts as $contact) {
            $contactId           = (int) $contact['id'];
            $contactIds[]        = $contactId;
            $listIds[$contactId] = (int) $contact['listId'];
        }

        $loadedContacts = [];
        $sendFailure    = null;
        $partialFailure = null;
        try {
            $sendResults = $this->smsModel->sendSms($sms, $contactIds, [
                'channel' => ['sms', $sms->getId()],
                'listId'  => $listIds,
            ], $loadedContacts);
            $result->process($sendResults, count($contactIds));
        } catch (PartialBatchException $exception) {
            // Only completed/pre-filtered outcomes are included. The collection that
            // raised the transport exception remains unprocessed and retryable.
            $result->process($exception->getResults());
            $result->executionFailed();
            $partialFailure = $exception;
        } catch (\Throwable $exception) {
            $sendFailure = $exception;
        }

        $executionFailureReported = false;
        try {
            $this->leadRepository->detachEntities($loadedContacts);
        } catch (\Throwable $exception) {
            if (null === $sendFailure && null === $partialFailure) {
                // Submission has already completed. Preserve those known outcomes while
                // reporting that cleanup could not be completed.
                $result->executionFailed();
                $this->reportExecutionFailure($sms, $exception);
                $executionFailureReported = true;
            }
        }

        if (null !== $sendFailure) {
            throw $sendFailure;
        }

        if (null !== $partialFailure) {
            $this->reportExecutionFailureClass($sms, $partialFailure->getOriginalExceptionClass());

            try {
                // Do not advance the cursor past an unknown transport outcome. Completed
                // contacts have stats and are excluded; the failed collection can retry.
                $result->setRemainingCount($this->broadcastQuery->getPendingCount($sms, $partition));
            } catch (\Throwable) {
                // The transport failure is already represented and logged. Avoid
                // replacing or double-reporting it with secondary bookkeeping failures.
            }

            return ['result' => $result, 'hasMore' => false];
        }

        $nextContactId = $contactIds[array_key_last($contactIds)] + 1;
        if (null !== $partition->getMaxContactId() && $nextContactId > $partition->getMaxContactId()) {
            $result->setRemainingCount(0);

            return ['result' => $result, 'hasMore' => false];
        }

        $hasMore = true;
        try {
            $partition->setBatchMinContactId($nextContactId);
            if ($countRemaining) {
                $result->setRemainingCount($this->broadcastQuery->getPendingCount($sms, $partition));
                $hasMore = $result->getRemainingCount() > 0;
            }
        } catch (\Throwable $exception) {
            $hasMore = false;
            // Submission has already completed. Preserve those known outcomes while
            // reporting that cursor/count bookkeeping could not be completed.
            if (!$executionFailureReported) {
                $result->executionFailed();
                $this->reportExecutionFailure($sms, $exception);
            }
        }

        return ['result' => $result, 'hasMore' => $hasMore];
    }

    private function reportExecutionFailure(Sms $sms, \Throwable $exception): void
    {
        $this->reportExecutionFailureClass($sms, $exception::class);
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    private function reportExecutionFailureClass(Sms $sms, string $exceptionClass): void
    {
        $this->logger->error('SMS broadcast execution failed.', [
            'smsId'          => $sms->getId(),
            'exceptionClass' => $exceptionClass,
        ]);
    }
}
