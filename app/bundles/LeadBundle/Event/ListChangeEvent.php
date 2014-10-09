<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ListChangeEvent
 *
 * @package Mautic\LeadBundle\Event
 */
class ListChangeEvent extends Event
{

    private $lead;
    private $list;
    private $added;

    /**
     * @param Lead $lead
     * @param List $list
     */
    public function __construct(Lead $lead, LeadList $list, $added = true)
    {
        $this->lead   = $lead;
        $this->list   = $list;
        $this->added  = $added;
    }

    /**
     * Returns the Lead entity
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return LeadList|List
     */
    public function getList()
    {
       return $this->list;
    }

    /**
     * @return bool
     */
    public function wasAdded()
    {
        return $this->added;
    }

    /**
     * @return bool
     */
    public function wasRemoved()
    {
        return !$this->added;
    }
}