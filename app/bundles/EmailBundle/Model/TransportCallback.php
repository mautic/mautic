<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Model\DoNotContact;

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
     * TransportCallback constructor.
     *
     * @param DoNotContact  $dncModel
     * @param ContactFinder $finder
     */
    public function __construct(DoNotContact $dncModel, ContactFinder $finder)
    {
        $this->dncModel = $dncModel;
        $this->finder   = $finder;
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
            $email   = $result->getStat()->getEmail();
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
}
