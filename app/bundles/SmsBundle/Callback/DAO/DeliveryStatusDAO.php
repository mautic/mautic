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

class DeliveryStatusDAO extends AbstractActionDAO
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
}
