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
use Mautic\CoreBundle\Helper\MailHelper;
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
     * @var MailHelper
     */
    private $helper;

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var string
     */
    private $plainText = '';

    /**
     * @var string
     */
    private $subject = '';

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
    private $tokens = array();

    /**
     * @var internalSend
     */
    private $internalSend = false;

    /**
     * @param MailHelper $helper
     * @param array      $args
     */
    public function __construct(MailHelper $helper = null, $args = array())
    {
        $this->helper = $helper;

        if (isset($args['content'])) {
            $this->content = $args['content'];
        }

        if (isset($args['plainText'])) {
            $this->plainText = $args['plainText'];
        }

        if (isset($args['subject'])) {
            $this->subject = $args['subject'];
        }

        if (isset($args['idHash'])) {
            $this->idHash = $args['idHash'];
        }

        if (isset($args['lead'])) {
            $this->lead = $args['lead'];
        }

        if (isset($args['source'])) {
            $this->source = $args['source'];
        }

        if (isset($args['tokens'])) {
            $this->tokens = $args['tokens'];
        }

        if (isset($args['internalSend'])) {
            $this->internalSend = $args['internalSend'];
        } elseif ($helper !== null) {
            $this->internalSend = $helper->isInternalSend();
        }
    }


    /**
     * Check if this email is an internal send or to the lead; if an internal send, don't append lead tracking
     *
     * @return internalSend
     */
    public function isInternalSend()
    {
        return $this->internalSend;
    }

    /**
     * Return if the transport and mailer is in batch mode (tokenized emails)
     *
     * @return bool
     */
    public function inTokenizationMode()
    {
        return ($this->helper !== null) ? $this->helper->inTokenizationMode() : false;
    }

    /**
     * Returns the Email entity
     *
     * @return Email
     */
    public function getEmail()
    {
        return ($this->helper !== null) ? $this->helper->getEmail() : null;
    }

    /**
     * Get email content
     *
     * @param $replaceTokens
     *
     * @return array
     */
    public function getContent($replaceTokens = false)
    {
        if ($this->helper !== null) {
            $content = $this->helper->getBody();
        } else {

            $content = $this->content;
        }

        return ($replaceTokens) ? str_replace(array_keys($this->getTokens()), $this->getTokens(), $content) : $content;
    }

    /**
     * Set email content
     *
     * @param $content
     */
    public function setContent($content)
    {
        if ($this->helper !== null) {
            $this->helper->setBody($content);
        } else {
            $this->content = $content;
        }
    }

    /**
     * Get email content
     *
     * @return array
     */
    public function getPlainText()
    {
        if ($this->helper !== null) {

            return $this->helper->getPlainText();
        } else {

            return $this->plainText;
        }
    }

    /**
     * @param $content
     */
    public function setPlainText($content)
    {
        if ($this->helper !== null) {
            $this->helper->setPlainText($content);
        } else {
            $this->plainText = $content;
        }
    }

    /**
     * Get the MailHelper object
     *
     * @return MailHelper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return ($this->helper !== null) ? $this->helper->getLead() : $this->lead;
    }

    /**
     * @return string
     */
    public function getIdHash()
    {
        return ($this->helper !== null) ? $this->helper->getIdHash() : $this->idHash;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return ($this->helper !== null) ? $this->helper->getSource() : $this->source;
    }

    /**
     * @param array $tokens
     */
    public function addTokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
    }

    /**
     * @param $key
     * @param $value
     */
    public function addToken($key, $value)
    {
        $this->tokens[$key] = $value;
    }

    /**
     * Get token array
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }
}
