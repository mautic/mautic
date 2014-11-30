<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Integration
 * @ORM\Table(name="addon_integration_settings")
 * @ORM\Entity(repositoryClass="Mautic\AddonBundle\Entity\IntegrationRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Integration
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Addon", inversedBy="integrations")
     * @ORM\JoinColumn(name="addon_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $addon;

    /**
     * @ORM\Column(type="string")
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
    public function getId ()
    {
        return $this->id;
    }

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

    /**
     * @return mixed
     */
    public function getAddon ()
    {
        return $this->addon;
    }

    /**
     * @param mixed $addon
     */
    public function setAddon (Addon $addon)
    {
        $this->addon = $addon;
    }
}
