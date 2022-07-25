<?php

namespace Mautic\EmailBundle\MonitoredEmail\Search;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;

class Result
{
    /**
     * @var Stat
     */
    private $stat;

    /**
     * @var Lead[]
     */
    private $contacts = [];

    /**
     * @var string
     */
    private $email;

    /**
     * @return Stat
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
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
     * @return Result
     */
    public function addContact(Lead $contact)
    {
        $this->contacts[] = $contact;

        return $this;
    }

    /**
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
