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
use JMS\Serializer\Annotation as Serializer;

/**
 * Class LeadFieldValue
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\LeadFieldValueRepository")
 * @ORM\Table(name="lead_field_values", uniqueConstraints={@ORM\UniqueConstraint(name="leadfield_idx", columns={"lead_id", "field_id"})})
 * @Serializer\ExclusionPolicy("all")
 */
class LeadFieldValue
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Lead", inversedBy="fields")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=false)
     */
    private $lead;

    /**
     * @ORM\ManyToOne(targetEntity="LeadField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $field;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $value;

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
     * Set value
     *
     * @param string $value
     * @return LeadFieldValue
     */
    public function setValue($value)
    {
        //the model only sets the value if its new or changed
        $this->changes[$this->field->getAlias()] = array($this->value, $value);

        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set lead
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @return LeadFieldValue
     */
    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead = null)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead
     *
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set field
     *
     * @param \Mautic\LeadBundle\Entity\LeadField $field
     * @return LeadFieldValue
     */
    public function setField(\Mautic\LeadBundle\Entity\LeadField $field = null)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return \Mautic\LeadBundle\Entity\LeadField
     */
    public function getField()
    {
        return $this->field;
    }
}
