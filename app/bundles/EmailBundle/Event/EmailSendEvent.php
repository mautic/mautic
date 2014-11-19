<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;

/**
 * Class EmailSendEvent
 *
 * @package Mautic\EmailBundle\Event
 */
class EmailSendEvent extends CommonEvent
{

    /**
     * @var mixed
     */
    private $content;

    /**
     * @var AssetsHelper
     */
    private $slotsHelper;

    /**
     * @var string
     */
    private $idHash;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $source;

    /**
     * @param Email  $email
     * @param Lead   $lead
     * @param string $idHash
     * @param array  $source
     */
    public function __construct(Email &$email, $lead = null, $idHash = '', $source = array())
    {
        $this->entity  =& $email;
        $this->content = $email->getContent();
        $this->idHash  = $idHash;
        $this->lead    = $lead;
        $this->source  = $source;
    }

    /**
     * Returns the Email entity
     *
     * @return Email
     */
    public function getEmail()
    {
        return $this->entity;
    }

    /**
     * Sets the Email entity
     *
     * @param Email $email
     */
    public function setEmail(Email $email)
    {
        $this->entity = $email;
    }

    /**
     * Get email content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set email content
     *
     * @param array $content
     */
    public function setContent(array $content)
    {
        $this->content = $content;
    }

    /**
     * Set the slots helper for content
     *
     * @param AssetsHelper $slotsHelper
     */
    public function setSlotsHelper($slotsHelper)
    {
        $this->slotsHelper = $slotsHelper;
    }

    /**
     * Get the slots helper that can be used to add scripts/stylesheets to the header
     *
     * @return AssetsHelper
     */
    public function getSlotsHelper()
    {
        return $this->slotsHelper;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return string
     */
    public function getIdHash()
    {
        return $this->idHash;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return $this->source;
    }
}
