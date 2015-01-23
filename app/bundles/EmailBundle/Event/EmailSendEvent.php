<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
     * @var array
     */
    private $content;

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
     * @var array
     */
    private $tokens;

    /**
     * @param string $content
     * @param Email  $email
     * @param Lead   $lead
     * @param string $idHash
     * @param array  $source
     */
    public function __construct($content, Email $email = null, $lead = null, $idHash = '', $source = array(), $tokens = array())
    {
        $this->content = $content;
        $this->entity  = $email;
        $this->idHash  = $idHash;
        $this->lead    = $lead;
        $this->source  = $source;
        $this->tokens  = $tokens;
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
     * @return array
     */
    public function getContent($finalContent = false)
    {
        if ($finalContent && !empty($this->tokens)) {
            // Parse custom tokens
            $this->content = str_ireplace($this->tokens['search'], $this->tokens['replace'], $this->content);
        }
        return $this->content;
    }

    /**
     * Set email content
     *
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
