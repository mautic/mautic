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

/**
 * Class EmailSendEvent
 *
 * @package Mautic\EmailBundle\Event
 */
class EmailSendEvent extends CommonEvent
{

    private $content;
    private $slotsHelper;
    private $idHash;
    private $lead;
    private $source;

    /**
     * @param Email $email
     * @param       $lead
     * @param       $idHash
     * @param       $source
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
     * @param $slotsHelper
     */
    public function setSlotsHelper($slotsHelper)
    {
        $this->slotsHelper = $slotsHelper;
    }

    /**
     * Get the slots helper that can be used to add scripts/stylesheets to the header
     *
     * @return mixed
     */
    public function getSlotsHelper()
    {
        return $this->slotsHelper;
    }

    /**
     * @return null
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