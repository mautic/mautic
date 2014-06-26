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
 * Class Submission
 * @ORM\Entity(repositoryClass="Mautic\FormBundle\Entity\SubmissionRepository")
 * @ORM\Table(name="form_submissions")
 * @Serializer\ExclusionPolicy("all")
 */
class Submission
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Form", inversedBy="submissions")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     **/
    private $form;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "refresh", "detach"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=false)
     */
    private $ipAddress;

    /**
     * @ORM\Column(name="date_submitted", type="datetime")
     */
    private $dateSubmitted;

    /**
     * @ORM\Column(type="string")
     */
    private $referer;

    /**
     * @ORM\OneToMany(targetEntity="Result", mappedBy="submission", cascade={"persist", "remove", "refresh", "detach"})
     */
    private $results;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->results = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set dateSubmitted
     *
     * @param \DateTime $dateSubmitted
     * @return Submission
     */
    public function setDateSubmitted($dateSubmitted)
    {
        $this->dateSubmitted = $dateSubmitted;

        return $this;
    }

    /**
     * Get dateSubmitted
     *
     * @return \DateTime
     */
    public function getDateSubmitted()
    {
        return $this->dateSubmitted;
    }

    /**
     * Set referer
     *
     * @param string $referer
     * @return Submission
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set form
     *
     * @param \Mautic\FormBundle\Entity\Form $form
     * @return Submission
     */
    public function setForm(\Mautic\FormBundle\Entity\Form $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get form
     *
     * @return \Mautic\FormBundle\Entity\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     * @return Submission
     */
    public function setIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddress = null)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Add results
     *
     * @param \Mautic\FormBundle\Entity\Result $results
     * @return Submission
     */
    public function addResult(\Mautic\FormBundle\Entity\Result $results)
    {
        $this->results[] = $results;

        return $this;
    }

    /**
     * Remove results
     *
     * @param \Mautic\FormBundle\Entity\Result $results
     */
    public function removeResult(\Mautic\FormBundle\Entity\Result $results)
    {
        $this->results->removeElement($results);
    }

    /**
     * Get results
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResults()
    {
        return $this->results;
    }
}
