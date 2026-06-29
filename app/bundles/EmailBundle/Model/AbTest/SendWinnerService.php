<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model\AbTest;

use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\CoreBundle\Model\AbTest\AbTestResultService;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Exception\NotReadyToSendWinnerException;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * Service for sending a winner variant email to remaining contacts.
 */
final class SendWinnerService
{
    /**
     * @var array<string>
     */
    private array $outputMessages = [];

    private bool $tryAgain = false;

    public function __construct(private EmailModel $emailModel, private AbTestResultService $abTestResultService, private AbTestSettingsService $abTestSettingsService)
    {
    }

    /**
     * @throws \Exception
     */
    public function processWinnerEmails(?int $emailId = null): void
    {
        if (null === $emailId) {
            $emails = $this->emailModel->getEmailsToSendWinnerVariant();
        } else {
            $emailEntity = $this->emailModel->getEntity($emailId);

            if (!$emailEntity instanceof Email) {
                throw new RecordNotFoundException('Email id '.$emailId.' not found');
            }

            $emails = [$emailEntity];
        }

        if (empty($emails)) {
            $this->addOutputMessage('No emails to send');

            return;
        }

        foreach ($emails as $email) {
            try {
                $this->processWinnerEmail($email);
            } catch (NotReadyToSendWinnerException $e) {
                $this->addOutputMessage($e->getMessage());
            }
        }

        if (null === $emailId) {
            // it has to be false for multiple emails
            $this->tryAgain = false;
        }
    }

    /**
     * @return array<string>
     */
    public function getOutputMessages(): array
    {
        return $this->outputMessages;
    }

    public function shouldTryAgain(): bool
    {
        return $this->tryAgain;
    }

    /**
     * @throws NotReadyToSendWinnerException
     */
    private function processWinnerEmail(Email $email): void
    {
        $this->addOutputMessage(sprintf("\n\nProcessing email id #%d", $email->getId()));

        $abTestSettings = $this->abTestSettingsService->getAbTestSettings($email);

        if (true === $this->isAllowedToSendWinner($email, $abTestSettings)) {
            $winner = $this->getWinner($email, $abTestSettings['winnerCriteria']);

            if (null === $winner) {
                throw new NotReadyToSendWinnerException('Winner email entity could not be found.');
            }

            $this->emailModel->convertWinnerVariant($winner);

            // send winner email
            $this->addOutputMessage('Winner email '.$winner->getId().' has been sent to remaining contacts.');
        }
    }

    /**
     * @param array<int|string> $abTestSettings
     *
     * @throws NotReadyToSendWinnerException
     */
    private function isAllowedToSendWinner(Email $email, array $abTestSettings): bool
    {
        // g et A/B test information
        [$parent, $children] = $email->getVariants();

        if (!array_key_exists('sendWinnerDelay', $abTestSettings) || $abTestSettings['sendWinnerDelay'] < 1) {
            throw new NotReadyToSendWinnerException('Amount of time to send winner email not specified in AB test variant settings.');
        }

        if (!array_key_exists('totalWeight', $abTestSettings) || AbTestSettingsService::DEFAULT_TOTAL_WEIGHT === $abTestSettings['totalWeight']) {
            throw new NotReadyToSendWinnerException('Total weight has to be smaller than 100.');
        }

        if (0 === count($children)) {
            // no variants
            throw new NotReadyToSendWinnerException("Email doesn't have variants");
        }

        if (false === $this->emailModel->isReadyToSendWinner($parent->getId(), $abTestSettings['sendWinnerDelay'])) {
            $this->tryAgain = true; // we should reschedule the call in this case
            // too early
            throw new NotReadyToSendWinnerException("Predetermined amount of time hasn't passed yet");
        }

        return true;
    }

    /**
     * @throws NotReadyToSendWinnerException
     */
    private function getWinner(Email $parentVariant, string $winnerCriteria): ?Email
    {
        $criteria      = $this->emailModel->getBuilderComponents($parentVariant, 'abTestWinnerCriteria');
        $abTestResults = $this->abTestResultService->getAbTestResult($parentVariant, $criteria['criteria'][$winnerCriteria]);
        $winners       = $abTestResults['winners'] ?? [];

        if (empty($winners)) {
            $this->tryAgain = true; // we should reschedule the call in this case
            // no winners
            throw new NotReadyToSendWinnerException('No winner yet.');
        }

        $this->addOutputMessage('Winner ids: '.implode(',', $winners));

        return $this->emailModel->getEntity($winners[0]);
    }

    private function addOutputMessage(string $message): void
    {
        $this->outputMessages[] = $message;
    }
}
