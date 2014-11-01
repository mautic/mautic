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
 * Class ApplicationIntegration
 * @ORM\Table(name="mapper_application_integration_settings")
 * @ORM\Entity(repositoryClass="Mautic\MapperBundle\Entity\ApplicationIntegrationRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class ApplicationIntegration
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
    private $name;

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
     * @return mixed
     */
    public function getName ()
    {
        return $this->name;
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
     * @param mixed $name
     * @return ApplicationIntegration
     */
    public function setName ($name)
    {
        $this->name = $name;

        return $this;
    }
}
