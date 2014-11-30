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
 * Class ApplicationObjectMapper
 * @ORM\Table(name="mapper_application_object_mapper_settings")
 * @ORM\Entity(repositoryClass="MauticAddon\MauticCrmBundle\Entity\ApplicationObjectMapperRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class ApplicationObjectMapper
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="MauticAddon\MauticCrmBundle\Entity\ApplicationClient")
     * @ORM\JoinColumn(name="application_client_id", referencedColumnName="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     */
    private $applicationClientId;

    /**
     * @ORM\Column(type="string", name="object_name")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     */
    private $objectName;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     */
    private $nrFields = 0;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     */
    private $points = 0;

    /**
     * @ORM\Column(type="array", name="mapped_fields", nullable=true)
     */
    private $mappedFields = array();

    /**
     * @return string
     */
    public function getObjectName ()
    {
        return $this->objectName;
    }

    /**
     * @param $points
     * @return ApplicationObjectMapper
     */
    public function setObjectName ($name)
    {
        $this->objectName = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPoints ()
    {
        return $this->points;
    }

    /**
     * @param $points
     * @return ApplicationObjectMapper
     */
    public function setPoints ($points)
    {
        $this->points = $points;

        return $this;
    }

    public function setNrFields($nrFields)
    {
        $this->nrFields = intval($nrFields);

        return $this;
    }

    public function getNrFields()
    {
        return $this->nrFields;
    }

    /**
     * @return mixed
     */
    public function getMappedFields ()
    {
        return $this->mappedFields;
    }

    /**
     * @param mixed $fields
     * @return ApplicationObjectMapper
     */
    public function setMappedFields (array $fields)
    {
        $this->mappedFields = $fields;

        return $this;
    }

    /**
     * Set owner
     *
     * @param ApplicationClient $client
     * @return ApplicationObjectMapper
     */
    public function setApplicationClientId(ApplicationClient $client)
    {
        $this->applicationClientId = $client;

        return $this;
    }

    /**
     * Get owner
     *
     * @return ApplicationIntegration
     */
    public function getApplicationClientId()
    {
        return $this->applicationClientId;
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

    public function getTitle()
    {
        return $this->getObjectName();
    }
}
