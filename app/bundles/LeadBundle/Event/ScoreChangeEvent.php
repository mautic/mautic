<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class ScoreChangeEvent
 *
 * @package Mautic\LeadBundle\Event
 */
class ScoreChangeEvent extends CommonEvent
{

    protected $old;
    protected $new;

    /**
     * @param Lead $lead
     * @param bool $isNew
     */
    public function __construct(Lead &$lead, $old, $new)
    {
        $this->entity =& $lead;
        $this->old = (int) $old;
        $this->new = (int) $new;
    }

    /**
     * Returns the Lead entity
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->entity;
    }

    /**
     * Returns the new score
     *
     * @return int
     */
    public function getNewScore()
    {
        return $this->new;
    }

    /**
     * Returns the old score
     *
     * @return int
     */
    public function getOldScore()
    {
        return $this->old;
    }
}