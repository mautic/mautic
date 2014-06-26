<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Result
 * @ORM\Entity()
 * @ORM\Table(name="form_results")
 * @Serializer\ExclusionPolicy("all")
 */
class Result
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Field")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    private $field;

    /**
     * @ORM\ManyToOne(targetEntity="Submission", inversedBy="results")
     * @ORM\JoinColumn(name="submission_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    private $submission;

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
     * @return Result
     */
    public function setValue($value)
    {
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
     * Set field
     *
     * @param \Mautic\FormBundle\Entity\Field $field
     * @return Result
     */
    public function setField(\Mautic\FormBundle\Entity\Field $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return \Mautic\FormBundle\Entity\Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set submission
     *
     * @param \Mautic\FormBundle\Entity\Submission $submission
     * @return Result
     */
    public function setSubmission(\Mautic\FormBundle\Entity\Submission $submission)
    {
        $this->submission = $submission;

        return $this;
    }

    /**
     * Get submission
     *
     * @return \Mautic\FormBundle\Entity\Submission
     */
    public function getSubmission()
    {
        return $this->submission;
    }
}
