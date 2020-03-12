<?php

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback\DAO;

use Doctrine\Common\Collections\ArrayCollection;

class DeliveryStatusDAO
{
    /**
     * @var bool
     */
    private $isDelivered = false;

    /**
     * @var bool
     */
    private $isRead = false;

    /**
     * @var string
     */
    private $trackingHash;

    /**
     * @var ArrayCollection
     */
    private $contacts;

    /**
     * @return bool
     */
    public function isDelivered()
    {
        return $this->isDelivered;
    }

    /**
     * @param bool $isDelivered
     */
    public function setIsDelivered()
    {
        $this->isDelivered = true;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        return $this->isRead;
    }

    /**
     * @param bool $isRead
     */
    public function setIsRead()
    {
        $this->isRead = true;
    }

    /**
     * @return string
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param string $trackingHash
     */
    public function setTrackingHash($trackingHash)
    {
        $this->trackingHash = $trackingHash;
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
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;
    }
}
