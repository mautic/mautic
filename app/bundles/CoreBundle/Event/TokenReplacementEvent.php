<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class CommonEvent
 */
class TokenReplacementEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @var array
     */
    protected $clickthrough = [];

    /**
     * TokenReplacementEvent constructor.
     *
     * @param string $content
     * @param Lead   $lead
     */
    public function __construct($content, Lead $lead = null, array $clickthrough = [])
    {
        $this->content = $content;
        $this->lead = $lead;
        $this->clickthrough = $clickthrough;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return array
     */
    public function getClickthrough()
    {
        if (!in_array('lead_id', $this->clickthrough)) {
            $this->clickthrough['lead_id'] = $this->lead->getId();
        }

        return $this->clickthrough;
    }

    /**
     * @param array $clickthrough
     */
    public function setClickthrough($clickthrough)
    {
        $this->clickthrough = $clickthrough;
    }
}
