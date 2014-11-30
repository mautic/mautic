<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class ApplicationIntegration
 * @ORM\Table(name="mapper_application_clients")
 * @ORM\Entity(repositoryClass="MauticAddon\MauticCrmBundle\Entity\ApplicationClientRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class ApplicationClient
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\Column(type="string")
     */
    private $alias;

    /**
     * @ORM\Column(type="string", name="application")
     */
    private $application;

    /**
     * @ORM\Column(type="array", name="api_keys")
     */
    private $apiKeys = array();

    /**
     * @return mixed
     */
    public function getApiKeys ()
    {
        return $this->apiKeys;
    }

    /**
     * @param mixed $apiKeys
     * @return ApplicationIntegration
     */
    public function setApiKeys (array $apiKeys)
    {
        $this->apiKeys = $apiKeys;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     * @return ApplicationIntegration
     */
    public function setName ($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName ()
    {
        return $this->name;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getTitle ()
    {
        return $this->title;
    }

    public function setTitle ($title)
    {
        $this->title = $title;
    }

    public function getApplication ()
    {
        return $this->application;
    }

    public function setApplication ($application)
    {
        $this->application = $application;
    }
}
