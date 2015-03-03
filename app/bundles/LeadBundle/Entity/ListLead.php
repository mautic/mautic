<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Lead
 * @ORM\Table(name="lead_lists_leads")
 * @ORM\Entity(repositoryClass="Mautic\LeadBundle\Entity\ListLeadRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class ListLead
{

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="LeadList", inversedBy="leads")
     * @ORM\JoinColumn(name="leadlist_id", onDelete="CASCADE")
     **/
    private $list;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Lead")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $lead;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     **/
    private $dateAdded;

    /**
     * @ORM\Column(name="manually_removed", type="boolean")
     */
    private $manuallyRemoved = false;

    /**
     * @ORM\Column(name="manually_added", type="boolean")
     */
    private $manuallyAdded = false;

    /**
     * @return \DateTime
     */
    public function getDateAdded ()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded ($date)
    {
        $this->dateAdded = $date;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead ($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return LeadList
     */
    public function getList ()
    {
        return $this->list;
    }

    /**
     * @param LeadList $leadList
     */
    public function setList ($leadList)
    {
        $this->list = $leadList;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved ()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved ($manuallyRemoved)
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded ()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded ($manuallyAdded)
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }
}
