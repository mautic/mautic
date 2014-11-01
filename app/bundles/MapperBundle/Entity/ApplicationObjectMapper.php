<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class ApplicationObjectMapper
 * @ORM\Table(name="mapper_application_object_mapper_settings")
 * @ORM\Entity(repositoryClass="Mautic\MapperBundle\Entity\ApplicationObjectMapperRepository")
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
     * @ORM\ManyToOne(targetEntity="Mautic\MapperBundle\Entity\ApplicationIntegration")
     * @ORM\JoinColumn(name="application_integration_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     */
    private $applicationIntegrationId;

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
     * @param \Mautic\MapperBundle\Entity\ApplicationIntegration $owner
     * @return ApplicationObjectMapper
     */
    public function setApplicationIntegrationId(ApplicationIntegration $applicationIntegration = null)
    {
        $this->applicationIntegrationId = $applicationIntegration;

        return $this;
    }

    /**
     * Get owner
     *
     * @return ApplicationIntegration
     */
    public function getApplicationIntegrationId()
    {
        return $this->applicationIntegrationId;
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
}
