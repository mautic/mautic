<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Search;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

class Result
{
    /**
     * @var Stat
     */
    protected $stat;

    /**
     * @var Lead[]
     */
    protected $contacts = [];

    /**
     * @var string
     */
    protected $email;

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * @param Stat $stat
     *
     * @return Result
     */
    public function setStat(Stat $stat)
    {
        $this->stat = $stat;

        if ($contact = $stat->getLead()) {
            $this->contacts[] = $contact;
        }

        return $this;
    }

    /**
     * @return Lead[]
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param Lead $contact
     *
     * @return Result
     */
    public function addContact(Lead $contact)
    {
        $this->contacts[] = $contact;

        return $this;
    }

    /**
     * @param array $contacts
     *
     * @return Lead[]
     */
    public function setContacts(array $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return Result
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
}
