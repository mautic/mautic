<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Callback\DAO;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\SmsBundle\Entity\Stat;

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
     * @var bool
     */
    private $isFailed = false;

    /**
     * @var ArrayCollection
     */
    private $contacts;

    /**
     * @var Stat
     */
    private $stat;

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
     * @return bool
     */
    public function isFailed()
    {
        return $this->isFailed;
    }

    /**
     * @param bool $isFailed
     */
    public function setIsFailed()
    {
        $this->isFailed = true;
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

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @param Stat $stat
     */
    public function setStat($stat)
    {
        $this->stat = $stat;
    }
}
