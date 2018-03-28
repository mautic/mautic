<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Stat;

use Mautic\EmailBundle\Entity\Stat;

class Reference
{
    /**
     * @var int
     */
    private $emailId;

    /**
     * @var int
     */
    private $leadId = 0;

    /**
     * @var
     */
    private $statId;

    /**
     * Reference constructor.
     *
     * @param Stat $stat
     */
    public function __construct(Stat $stat)
    {
        $this->statId  = $stat->getId();
        $this->emailId = $stat->getEmail()->getId();
        if ($lead = $stat->getLead()) {
            $this->leadId = $stat->getLead()->getId();
        }
    }

    /**
     * @return int
     */
    public function getEmailId()
    {
        return $this->emailId;
    }

    /**
     * @return int
     */
    public function getLeadId()
    {
        return $this->leadId;
    }

    /**
     * @return mixed
     */
    public function getStatId()
    {
        return $this->statId;
    }
}
