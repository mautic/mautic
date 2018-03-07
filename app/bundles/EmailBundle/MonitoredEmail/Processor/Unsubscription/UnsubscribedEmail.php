<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription;

class UnsubscribedEmail
{
    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var
     */
    private $unsubscriptionAddress;

    /**
     * UnsubscribedEmail constructor.
     *
     * @param string $contactEmail
     * @param string $unsubscriptionAddress
     */
    public function __construct($contactEmail, $unsubscriptionAddress)
    {
        $this->contactEmail          = $contactEmail;
        $this->unsubscriptionAddress = $unsubscriptionAddress;
    }

    /**
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * @return string
     */
    public function getUnsubscriptionAddress()
    {
        return $this->unsubscriptionAddress;
    }
}
