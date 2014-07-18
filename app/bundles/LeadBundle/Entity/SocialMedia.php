<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class SocialMedia
 * @ORM\Table(name="lead_socialmedia_tokens")
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\SocialMediaRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class SocialMedia
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $service;

    /**
     * @ORM\Column(type="boolean", name="is_published")
     */
    private $isPublished = false;

    /**
     * @ORM\Column(type="array", name="api_keys")
     */
    private $apiKeys = array();

    /**
     * @ORM\Column(type="array", name="lead_fields")
     */
    private $leadFields = array();


    /**
     * @return mixed
     */
    public function getApiKeys ()
    {
        return $this->apiKeys;
    }

    /**
     * @param mixed $apiKeys
     */
    public function setApiKeys (array $apiKeys)
    {
        $this->apiKeys = $apiKeys;
    }

    /**
     * @return mixed
     */
    public function getIsPublished ()
    {
        return $this->isPublished;
    }

    /**
     * @param mixed $isPublished
     */
    public function setIsPublished ($isPublished)
    {
        $this->isPublished = $isPublished;
    }

    /**
     * @return mixed
     */
    public function getLeadFields ()
    {
        return $this->leadFields;
    }

    /**
     * @param mixed $leadFields
     */
    public function setLeadFields (array $leadFields)
    {
        $this->leadFields = $leadFields;
    }

    /**
     * @return mixed
     */
    public function getService ()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService ($service)
    {
        $this->service = $service;
    }

    public function isPublished()
    {
        return $this->getIsPublished();
    }
}
