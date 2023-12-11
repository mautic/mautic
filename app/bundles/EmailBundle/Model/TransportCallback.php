<?php

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;

class TransportCallback
{
    public function __construct(
        private DoNotContact $dncModel,
        private ContactFinder $finder,
        private StatRepository $statRepository
    ) {
    }

    /**
     * @param string $hashId
     * @param string $comments
     * @param int    $dncReason
     */
    public function addFailureByHashId($hashId, $comments, $dncReason = DNC::BOUNCED): void
    {
        $result = $this->finder->findByHash($hashId);

        if ($contacts = $result->getContacts()) {
            $stat = $result->getStat();
            $this->updateStatDetails($stat, $comments, $dncReason);

            $email   = $stat->getEmail();
            $channel = ($email) ? ['email' => $email->getId()] : 'email';
            foreach ($contacts as $contact) {
                $this->dncModel->addDncForContact($contact->getId(), $channel, $dncReason, $comments);
            }
        }
    }

    /**
     * @param string   $address
     * @param string   $comments
     * @param int      $dncReason
     * @param int|null $channelId
     */
    public function addFailureByAddress($address, $comments, $dncReason = DNC::BOUNCED, $channelId = null): void
    {
        $result = $this->finder->findByAddress($address);

        if ($contacts = $result->getContacts()) {
            foreach ($contacts as $contact) {
                $channel = ($channelId) ? ['email' => $channelId] : 'email';
                $this->dncModel->addDncForContact($contact->getId(), $channel, $dncReason, $comments);
            }
        }
    }

    /**
     * @param int      $dncReason
     * @param int|null $channelId
     */
    public function addFailureByContactId($id, $comments, $dncReason = DNC::BOUNCED, $channelId = null): void
    {
        $channel = ($channelId) ? ['email' => $channelId] : 'email';
        $this->dncModel->addDncForContact($id, $channel, $dncReason, $comments);
    }

    private function updateStatDetails(Stat $stat, $comments, $dncReason): void
    {
        if (DNC::BOUNCED === $dncReason) {
            $stat->setIsFailed(true);
        }

        $openDetails = $stat->getOpenDetails();
        if (!isset($openDetails['bounces'])) {
            $openDetails['bounces'] = [];
        }
        $dtHelper                 = new DateTimeHelper();
        $openDetails['bounces'][] = [
            'datetime' => $dtHelper->toUtcString(),
            'reason'   => $comments,
        ];
        $stat->setOpenDetails($openDetails);
        $this->statRepository->saveEntity($stat);
    }
}
