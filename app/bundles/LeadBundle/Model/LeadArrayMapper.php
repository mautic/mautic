<?php

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class LeadArrayMapper.
 */
class LeadArrayMapper
{
    /**
     * @param array $data
     *
     * @return Lead
     */
    public function getLeadFromArray(array $data)
    {
        $lead = new Lead();
        $this->setId($lead, $data);
        $this->setTitle($lead, $data);
        $this->setFirstname($lead, $data);
        $this->setLastname($lead, $data);
        $this->setCompany($lead, $data);
        $this->setPosition($lead, $data);
        $this->setEmail($lead, $data);
        $this->setPhone($lead, $data);
        $this->setMobile($lead, $data);
        $this->setAddress($lead, $data);
        $this->setTimezone($lead, $data);
        $this->setCountry($lead, $data);
        $this->setOwner($lead, $data);
        $this->setPoints($lead, $data);
        $this->setIpAddresses($lead, $data);
        $this->setSocialCache($lead, $data);
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setId(Lead $lead, array $data)
    {
        if (array_key_exists('id', $data)) {
            $lead->setId($data['id']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setTitle(Lead $lead, array $data)
    {
        if (array_key_exists('title', $data)) {
            $lead->setTitle($data['title']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setFirstname(Lead $lead, array $data)
    {
        if (array_key_exists('firstname', $data)) {
            $lead->setFirstname($data['firstname']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setLastname(Lead $lead, array $data)
    {
        if (array_key_exists('lastname', $data)) {
            $lead->setLastname($data['lastname']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setCompany(Lead $lead, array $data)
    {
        if (array_key_exists('company', $data)) {
            $lead->setCompany($data['company']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setPosition(Lead $lead, array $data)
    {
        if (array_key_exists('position', $data)) {
            $lead->setPosition($data['position']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setEmail(Lead $lead, array $data)
    {
        if (array_key_exists('position', $data)) {
            $lead->setEmail($data['position']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setPhone(Lead $lead, array $data)
    {
        if (array_key_exists('phone', $data)) {
            $lead->setPhone($data['phone']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setMobile(Lead $lead, array $data)
    {
        if (array_key_exists('mobile', $data)) {
            $lead->setMobile($data['mobile']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setAddress(Lead $lead, array $data)
    {
        if (array_key_exists('address1', $data)) {
            $lead->setAddress1($data['address1']);
        }
        if (array_key_exists('address2', $data)) {
            $lead->setAddress2($data['address2']);
        }
        if (array_key_exists('city', $data)) {
            $lead->setCity($data['city']);
        }
        if (array_key_exists('state', $data)) {
            $lead->setState($data['state']);
        }
        if (array_key_exists('zipcode', $data)) {
            $lead->setZipcode($data['zipcode']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setTimezone(Lead $lead, array $data)
    {
        if (array_key_exists('timezone', $data)) {
            $lead->setTimezone($data['timezone']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setCountry(Lead $lead, array $data)
    {
        if (array_key_exists('country', $data)) {
            $lead->setCountry($data['country']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setOwner(Lead $lead, array $data)
    {
        if (array_key_exists('owner', $data)) {
            $lead->setOwner($data['owner']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setPoints(Lead $lead, array $data)
    {
        if (array_key_exists('points', $data)) {
            $lead->setPoints($data['points']);
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setIpAddresses(Lead $lead, array $data)
    {
        if (array_key_exists('ip_addresses', $data)) {
            foreach ($data['ip_addresses'] as $ipAddress) {
                $lead->addIpAddress($ipAddress);
            }
        }
    }

    /**
     * @param Lead  $lead
     * @param array $data
     */
    private function setSocialCache(Lead $lead, array $data)
    {
        if (array_key_exists('social_cache', $data)) {
            $lead->setSocialCache($data['social_cache']);
        }
    }
}
