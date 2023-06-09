<?php

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
class SendWinnerService
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    /**
     * @var AbTestResultService
     */
    private $abTestResultService;

    /**
     * @var AbTestSettingsService
     */
    private $abTestSettingsService;

    /**
     * @var array<string>
     */
    private $outputMessages;

    /**
     * @var bool
     */
    private $tryAgain = false;

    public function __construct(
        EmailModel $emailModel,
        AbTestResultService $abTestResultService,
        AbTestSettingsService $abTestSettingsService)
    {
        $this->emailModel            = $emailModel;
        $this->abTestResultService   = $abTestResultService;
        $this->abTestSettingsService = $abTestSettingsService;
    }

    /**
     * @param int|null $emailId
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function processWinnerEmails($emailId = null): void
    {
        if (null === $emailId) {
            $emails = $this->emailModel->getEmailsToSendWinnerVariant();
        } else {
            $emailEntity = $this->emailModel->getEntity($emailId);

            if (empty($emailEntity)) {
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
    public function getOutputMessages()
    {
        return $this->outputMessages;
    }

    /**
     * @return bool
     */
    public function shouldTryAgain()
    {
        return $this->tryAgain;
    }

    /**
     * @throws NotReadyToSendWinnerException
     * @throws \ReflectionException
     */
    private function processWinnerEmail(Email $email): void
    {
        $this->addOutputMessage(sprintf("\n\nProcessing email id #%d", $email->getId()));

        $abTestSettings = $this->abTestSettingsService->getAbTestSettings($email);

        if (true === $this->isAllowedToSendWinner($email, $abTestSettings)) {
            $winner = $this->getWinner($email, $abTestSettings['winnerCriteria']);

            $this->emailModel->convertWinnerVariant($winner);

            // send winner email
            $this->addOutputMessage('Winner email '.$winner->getId().' has been sent to remaining contacts.');
        }
    }

    /**
     * @param array<int|string> $abTestSettings
     *
     * @return bool
     *
     * @throws NotReadyToSendWinnerException
     */
    private function isAllowedToSendWinner(Email $email, $abTestSettings)
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
     * @param string $winnerCriteria
     *
     * @return Email|null
     *
     * @throws \ReflectionException
     * @throws NotReadyToSendWinnerException
     */
    private function getWinner(Email $parentVariant, $winnerCriteria)
    {
        $criteria      = $this->emailModel->getBuilderComponents($parentVariant, 'abTestWinnerCriteria');
        $abTestResults = $this->abTestResultService->getAbTestResult($parentVariant, $criteria['criteria'][$winnerCriteria]);
        $winners       = $abTestResults['winners'];

        if (empty($winners)) {
            $this->tryAgain = true; // we should reschedule the call in this case
            // no winners
            throw new NotReadyToSendWinnerException('No winner yet.');
        }

        $this->addOutputMessage('Winner ids: '.implode(',', $winners));

        $winner = $this->emailModel->getEntity($winners[0]);

        return $winner;
    }

    /**
     * @param string $message
     */
    private function addOutputMessage($message): void
    {
        $this->outputMessages[] = $message;
    }
}
