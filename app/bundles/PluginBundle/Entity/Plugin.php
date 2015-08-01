<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class Plugin
 * @ORM\Table(name="plugins", uniqueConstraints={@ORM\UniqueConstraint(name="unique_bundle", columns={"bundle"})})
 * @ORM\Entity(repositoryClass="Mautic\PluginBundle\Entity\PluginRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Plugin extends CommonEntity
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="is_missing", type="boolean")
     */
    private $isMissing = false;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $bundle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $version;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity="Integration", mappedBy="plugin", indexBy="id", fetch="EXTRA_LAZY")
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
     * @return mixed
     */
    public function getIntegrations ()
    {
        return $this->integrations;
    }

    /**
     * @return mixed
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription ($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getVersion ()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion ($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getIsMissing ()
    {
        return $this->isMissing;
    }

    /**
     * @param mixed $isMissing
     */
    public function setIsMissing ($isMissing)
    {
        $this->isMissing = $isMissing;
    }

    /**
     * @return mixed
     */
    public function getAuthor ()
    {
        return $this->author;
    }

    /**
     * @param mixed $author
     */
    public function setAuthor ($author)
    {
        $this->author = $author;
    }
}
