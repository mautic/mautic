<?php


namespace Mautic\EmailBundle\Model\AbTest;


use Mautic\CoreBundle\Model\AbTest\AbTestResultService;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;

/**
 * Class SendWinnerService
 *
 * @package Mautic\EmailBundle\Model\AbTest
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
     * @var array
     */
    private $outputMessages;

    /**
     * @var bool
     */
    private $completed = false;

    /**
     * @var bool
     */
    private $tryAgain = false;

    /**
     * SendWinnerService constructor.
     *
     * @param EmailModel $emailModel
     * @param AbTestResultService $abTestResultService
     * @param AbTestSettingsService $abTestSettingsService
     */
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
     * @param int $emailId
     *
     * @return bool|int
     *
     * @throws \ReflectionException
     */
    public function processWinnerEmails($emailId = null)
    {
        if ($emailId === null) {
            $emails = $this->emailModel->getEmailsToSendWinnerVariant();
        } else {
            $emails = [$this->emailModel->getEntity($emailId)];
        }

        if (empty($emails)) {
            $this->addOutputMessage('No emails to send');
        }

        foreach ($emails as $email) {
            $result = $this->processWinnerEmail($email);

            if ($emailId > 0) {
                return $result;
            }
        }

        $this->tryAgain = false;
        $this->completed = true;

        return $this->completed;
    }

    /**
     * @return array
     */
    public function getOutputMessages()
    {
        return $this->outputMessages;
    }

    /**
     * @return bool
     */
    public function tryAgain()
    {
        return $this->tryAgain;
    }

    /**
     * @param Email $email
     *
     * @return bool
     *
     * @throws \ReflectionException
     */
    private function processWinnerEmail(Email $email)
    {
        $this->addOutputMessage(sprintf("\n\nProcessing email id #%d", $email->getId()));

        //g et A/B test information
        list($parent, $children) = $email->getVariants();

        $abTestSettings = $this->abTestSettingsService->getAbTestSettings($parent);

        if ($this->emailModel->isReadyToSendWinner($parent->getId(), $abTestSettings['sendWinnerDelay']) === false) {
            // too early
            $this->addOutputMessage("Predetermined amount of time hasn't passed yet");

            $this->tryAgain = true; // we should reschedule the call in this case

            return $this->completed;
        }

        if (!array_key_exists('sendWinnerDelay', $abTestSettings) || $abTestSettings['sendWinnerDelay'] < 1) {
            $this->addOutputMessage('Amount of time to send winner email not specified in AB test variant settings.');

            return $this->completed;
        }

        if (!array_key_exists('totalWeight', $abTestSettings) || $abTestSettings['totalWeight'] === 100) {
            $this->addOutputMessage('Total weight has to be smaller than 100.');

            return $this->completed;
        }

        if (count($children) > 0) {
            $winner = $this->getWinner($parent, $abTestSettings['winnerCriteria']);
        } else {
            // no variants
            $this->addOutputMessage("Email doesn't have variants");

            return $this->completed;
        }

        if (empty($winner)) {
            // no winners
            $this->addOutputMessage('No winner yet.');

            $this->tryAgain = true; // we should reschedule the call in this case

            return $this->completed;
        }

        $this->emailModel->convertWinnerVariant($winner);

        // send winner email
        $this->emailModel->sendEmailToLists($winner);
        $this->addOutputMessage('Winner email '.$winner->getId().' has been sent to remaining contacts.');

        $this->completed = true;

        return $this->completed;
    }

    /**
     * @param Email $parentVariant
     * @param string $winnerCriteria
     *
     * @return Email|null
     *
     * @throws \ReflectionException
     */
    private function getWinner(Email $parentVariant, $winnerCriteria)
    {
        $criteria      = $this->emailModel->getBuilderComponents($parentVariant, 'abTestWinnerCriteria');
        $abTestResults = $this->abTestResultService->getAbTestResult($parentVariant, $criteria['criteria'][$winnerCriteria]);
        $winners       = $abTestResults['winners'];

        if (empty($winners)) {
            return null;
        }

        $this->addOutputMessage('Winner ids: '.implode($winners, ','));

        $winner = $this->emailModel->getEntity($winners[0]);

        return $winner;
    }

    /**
     * @param string $message
     */
    private function addOutputMessage($message)
    {
        $this->outputMessages[] = $message;
    }
}