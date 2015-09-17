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

/**
 * Class Addon
 *
 * Used by AddonBundleBase BC support
 *
 * @deprecated 1.1.4; will be removed in 2.0
 */
class Addon
{
    private $id;
    private $name;
    private $description;
    private $isMissing = false;
    private $bundle;
    private $version;
    private $author;
    private $integrations;

    public function __construct()
    {
        $this->integrations  = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return Addon
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Addon
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return Addon
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isIsMissing()
    {
        return $this->isMissing;
    }

    /**
     * @param boolean $isMissing
     *
     * @return Addon
     */
    public function setIsMissing($isMissing)
    {
        $this->isMissing = $isMissing;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @param mixed $bundle
     *
     * @return Addon
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     *
     * @return Addon
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     *
     * @return Addon
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getIntegrations()
    {
        return $this->integrations;
    }

    /**
     * @param ArrayCollection $integrations
     *
     * @return Addon
     */
    public function setIntegrations($integrations)
    {
        $this->integrations = $integrations;

        return $this;
    }
}
