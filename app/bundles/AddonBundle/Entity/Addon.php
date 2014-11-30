<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Addon
 * @ORM\Table(name="addons")
 * @ORM\Entity(repositoryClass="Mautic\AddonBundle\Entity\AddonRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Addon
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     */
    private $isEnabled = true;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $bundle;

    /**
     * @ORM\OneToMany(targetEntity="Integration", mappedBy="addon", indexBy="id", fetch="EXTRA_LAZY")
     */
    private $integrations;

    public function __construct()
    {
        $this->integrations  = new ArrayCollection();
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->id = null;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Addon
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set isEnabled
     *
     * @param boolean $isEnabled
     *
     * @return Addon
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * Get isEnabled
     *
     * @return boolean
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     *
     * @return Addon
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;
    }

    /**
     * Get bundle
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Check the publish status of an entity based on publish up and down datetimes
     *
     * @return string published|unpublished
     */
    public function getPublishStatus()
    {
        return $this->getIsEnabled() ? 'published' : 'unpublished';
    }

    /**
     * @return mixed
     */
    public function getIntegrations ()
    {
        return $this->integrations;
    }
}
