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
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Validator\Constraints\UniqueUserAlias;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class LeadList
 * @ORM\Table(name="lead_lists")
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\LeadListRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class LeadList extends FormEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"limited", "full"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"limited", "full"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=25)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"limited", "full"})
     */
    private $alias;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="array")
     */
    private $filters;

    /**
     * @ORM\Column(name="is_global", type="boolean")
     */
    private $isGlobal = false;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            array('message' => 'mautic.lead.list.name.notblank')
        ));

        $metadata->addConstraint(new UniqueUserAlias(array(
            'field'   => 'alias',
            'message' => 'mautic.lead.list.alias.unique'
        )));

        $metadata->addPropertyConstraint('filters', new Assert\Count(array(
            'min'        => 1,
            'minMessage' => 'mautic.lead.list.filters.notblank'
        )));
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
     * @param integer $name
     * @return LeadList
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return integer
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return LeadList
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return LeadList
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set filters
     *
     * @param array $filters
     * @return LeadList
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set isGlobal
     *
     * @param boolean $isGlobal
     * @return LeadList
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }

    /**
     * Get isGlobal
     *
     * @return boolean
     */
    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * Proxy function to getIsGlobal()
     *
     * @return bool
     */
    public function isGlobal()
    {
        return $this->getIsGlobal();
    }

    /**
     * Set alias
     *
     * @param string $alias
     * @return LeadList
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
