<?php

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\LeadBundle\Entity\Lead;

abstract class AbstractCallbackEvent
{
    /**
     * @var ArrayCollection
     */
    private $contacts;

    /**
     * @var string
     */
    private $trackingHash;

    /**
     * @return string
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param string $trackingHash
     *
     * @return AbstractCallbackEvent
     */
    public function setTrackingHash($trackingHash)
    {
        $this->trackingHash = $trackingHash;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param ArrayCollection $contacts
     *
     * @return AbstractCallbackEvent
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

    /**
     * @param Lead $contact
     *
     * @return $this
     */
    public function setContact(Lead $contact)
    {
        if (is_null($this->contacts)) {
            $this->contacts = new ArrayCollection();
        }

        $this->contacts->set($contact->getId(), $contact);

        return $this;
    }

}
