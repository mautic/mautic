<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class SocialNetwork
 * @ORM\Table(name="socialnetwork_settings")
 * @ORM\Entity(repositoryClass="Mautic\SocialBundle\Entity\SocialNetworkRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class SocialNetwork
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id()
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", name="is_published")
     */
    private $isPublished = false;

    /**
     * @ORM\Column(type="array", name="supported_features", nullable=true)
     */
    private $supportedFeatures = array();

    /**
     * @ORM\Column(type="array", name="api_keys")
     */
    private $apiKeys = array();

    /**
     * @ORM\Column(type="array", name="feature_settings", nullable=true)
     */
    private $featureSettings = array();

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
    public function getFeatureSettings ()
    {
        return $this->featureSettings;
    }

    /**
     * @param mixed $featureSettings
     */
    public function setFeatureSettings (array $featureSettings)
    {
        $this->featureSettings = $featureSettings;
    }

    /**
     * @return mixed
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName ($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function isPublished()
    {
        return $this->getIsPublished();
    }

    /**
     * @return mixed
     */
    public function getSupportedFeatures ()
    {
        return $this->supportedFeatures;
    }

    /**
     * @param mixed $supportedFeatures
     */
    public function setSupportedFeatures ($supportedFeatures)
    {
        $this->supportedFeatures = $supportedFeatures;
    }
}
