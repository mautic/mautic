<?php

namespace Mautic\EmailBundle\Model;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\BounceEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\EmailBundle\MonitoredEmail\Search\Result;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class TransportCallback
{
    /**
     * @var DoNotContact
     */
    private $dncModel;

    /**
     * @var ContactFinder
     */
    private $finder;

    /**
     * @var StatRepository
     */
    private $statRepository;
    private EventDispatcherInterface $dispatcher;

    /**
     * TransportCallback constructor.
     */
    public function __construct(
        DoNotContact $dncModel,
        ContactFinder $finder,
        StatRepository $statRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->dncModel       = $dncModel;
        $this->finder         = $finder;
        $this->statRepository = $statRepository;
        $this->dispatcher     = $dispatcher;
    }

    /**
     * @param string $hashId
     * @param string $comments
     * @param int    $dncReason
     */
    public function addFailureByHashId($hashId, $comments, $dncReason = DNC::BOUNCED)
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
    public function addFailureByAddress($address, $comments, $dncReason = DNC::BOUNCED, $channelId = null)
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
     * @param          $id
     * @param          $comments
     * @param int      $dncReason
     * @param int|null $channelId
     */
    public function addFailureByContactId($id, $comments, $dncReason = DNC::BOUNCED, $channelId = null)
    {
        $channel = ($channelId) ? ['email' => $channelId] : 'email';
        $this->dncModel->addDncForContact($id, $channel, $dncReason, $comments);
    }

    /**
     * @param $comments
     */
    private function updateStatDetails(Stat $stat, $comments, $dncReason)
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

    public function dispatchBounceEvent(Request $request, ?string $hashId = '', ?string $emailAddress = ''): void
    {
        if (empty($hashId) && empty($emailAddress)) {
            throw new \InvalidArgumentException();
        }

        $result = null;
        if (!empty($hashId)) {
            $result = $this->finder->findByHash($hashId);
        } elseif (!empty($emailAddress)) {
            $result = $this->finder->findByAddress($emailAddress);
        }

        if ($result instanceof Result && $contacts = $result->getContacts()) {
            $stat  = $result->getStat();
            $email = $stat->getEmail();
            $event = $this->dispatcher->dispatch(
                EmailEvents::EMAIL_ON_BOUNCE,
                new BounceEmailEvent($request, $email, $contacts)
            );
        }
    }
}
