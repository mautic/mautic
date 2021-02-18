<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Membership\Exception;

class RunLimitReachedException extends \Exception
{
    /**
     * @var int
     */
    private $contactsProcessed;

    /**
     * MaxContactsReachedException constructor.
     *
     * @param $contactsProcessed
     */
    public function __construct($contactsProcessed)
    {
        $this->contactsProcessed = (int) $contactsProcessed;

        parent::__construct();
    }

    /**
     * @return int
     */
    public function getContactsProcessed()
    {
        return $this->contactsProcessed;
    }
}
