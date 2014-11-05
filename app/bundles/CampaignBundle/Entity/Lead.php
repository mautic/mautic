<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Lead
 * @ORM\Table(name="campaign_leads")
 * @ORM\Entity(repositoryClass="Mautic\CampaignBundle\Entity\LeadRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Lead
{

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Campaign", inversedBy="leads")
     * @ORM\JoinColumn(onDelete="CASCADE")
     **/
    private $campaign;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
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
     * @return mixed
     */
    public function getDateAdded ()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $date
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
     * @return mixed
     */
    public function getCampaign ()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign ($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return mixed
     */
    public function getManuallyRemoved ()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param mixed $manuallyRemoved
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
     * @return mixed
     */
    public function getManuallyAdded ()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param mixed $manuallyAdded
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