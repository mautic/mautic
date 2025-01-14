<?php

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\LeadBundle\Entity\Lead;

class EmailSendEvent extends CommonEvent
{
    /**
     * @var MailHelper
     */
    private $helper;

    /**
     * @var Email|null
     */
    private $email;

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
     * @var string|null
     */
    private $idHash;

    /**
     * @var Lead|mixed[]|null
     */
    private $lead;

    /**
     * @var array
     */
    private $source;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var internalSend
     */
    private $internalSend = false;

    /**
     * @var array
     */
    private $textHeaders = [];

    /**
     * @var bool
     */
    private $isDynamicContentParsing;

    /**
     * @param array $args
     * @param bool  $isDynamicContentParsing
     */
    public function __construct(MailHelper $helper = null, $args = [], $isDynamicContentParsing = false)
    {
        $this->helper      = $helper;
        $this->content     = $args['content'] ?? '';
        $this->plainText   = $args['plainText'] ?? '';
        $this->subject     = $args['subject'] ?? '';
        $this->email       = $args['email'] ?? null;
        $this->idHash      = $args['idHash'] ?? null;
        $this->lead        = $args['lead'] ?? null;
        $this->source      = $args['source'] ?? [];
        $this->tokens      = $args['tokens'] ?? [];
        $this->textHeaders = $args['textHeaders'] ?? [];

        if (!$this->subject && $this->email instanceof Email) {
            $this->subject = $args['email']->getSubject();
        }

        if (isset($args['internalSend'])) {
            $this->internalSend = $args['internalSend'];
        } elseif (null !== $helper) {
            $this->internalSend = $helper->isInternalSend();
        }

        $this->isDynamicContentParsing = $isDynamicContentParsing;
    }

    /**
     * Check if this email is an internal send or to the lead; if an internal send, don't append lead tracking.
     *
     * @return internalSend
     */
    public function isInternalSend()
    {
        return $this->internalSend;
    }

    /**
     * Return if the transport and mailer is in batch mode (tokenized emails).
     *
     * @return bool
     */
    public function inTokenizationMode()
    {
        return (null !== $this->helper) ? $this->helper->inTokenizationMode() : false;
    }

    /**
     * Returns the Email entity.
     *
     * @return Email
     */
    public function getEmail()
    {
        return (null !== $this->helper) ? $this->helper->getEmail() : $this->email;
    }

    /**
     * Get email content.
     *
     * @param $replaceTokens
     *
     * @return string
     */
    public function getContent($replaceTokens = false)
    {
        if (null !== $this->helper) {
            $content = $this->helper->getBody();
        } else {
            $content = $this->content;
        }

        return ($replaceTokens) ? str_replace(array_keys($this->getTokens()), $this->getTokens(), $content) : $content;
    }

    /**
     * Set email content.
     *
     * @param $content
     */
    public function setContent($content)
    {
        if (null !== $this->helper) {
            $this->helper->setBody($content, 'text/html', null, true);
        } else {
            $this->content = $content;
        }
        $this->setGeneratedPlainText();
    }

    /**
     * Get email content.
     *
     * @return array
     */
    public function getPlainText()
    {
        if (null !== $this->helper) {
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
        if (null !== $this->helper) {
            $this->helper->setPlainText($content);
        } else {
            $this->plainText = $content;
        }
        $this->setGeneratedPlainText();
    }

    /**
     * Check if plain text is empty. If yes, generate it.
     */
    private function setGeneratedPlainText()
    {
        $htmlContent = $this->getContent();
        if ('' === $this->getPlainText() && '' !== $htmlContent) {
            $parser             = new PlainTextHelper();
            $generatedPlainText = $parser->setHtml($htmlContent)->getText();
            if ('' !== $generatedPlainText) {
                $this->setPlainText($generatedPlainText);
            }
        }
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        if (null !== $this->helper) {
            return $this->helper->getSubject();
        } else {
            return $this->subject;
        }
    }

    /**
     * @param string $subject
     *
     * @return EmailSendEvent
     */
    public function setSubject($subject)
    {
        if (null !== $this->helper) {
            $this->helper->setSubject($subject);
        } else {
            $this->subject = $subject;
        }
    }

    /**
     * Get the MailHelper object.
     *
     * @return MailHelper
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @return array
     */
    public function getLead()
    {
        return (null !== $this->helper) ? $this->helper->getLead() : $this->lead;
    }

    /**
     * @return string
     */
    public function getIdHash()
    {
        return (null !== $this->helper) ? $this->helper->getIdHash() : $this->idHash;
    }

    /**
     * @return array
     */
    public function getSource()
    {
        return (null !== $this->helper) ? $this->helper->getSource() : $this->source;
    }

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
     * Get token array.
     *
     * @return array
     */
    public function getTokens($includeGlobal = true)
    {
        $tokens = $this->tokens;

        if ($includeGlobal && null !== $this->helper) {
            $tokens = array_merge($this->helper->getGlobalTokens(), $tokens);
        }

        return $tokens;
    }

    /**
     * @param $name
     * @param $value
     */
    public function addTextHeader($name, $value)
    {
        if (null !== $this->helper) {
            $this->helper->addCustomHeader($name, $value);
        } else {
            $this->textHeaders[$name] = $value;
        }
    }

    /**
     * @return array
     */
    public function getTextHeaders()
    {
        return (null !== $this->helper) ? $this->helper->getCustomHeaders() : $this->textHeaders;
    }

    /**
     * Check if the listener should append it's own clickthrough in URLs or if the email tracking URL conversion process should take care of it.
     *
     * @return bool
     */
    public function shouldAppendClickthrough()
    {
        return !$this->isInternalSend() && null === $this->getEmail();
    }

    /**
     * Generate a clickthrough array for URLs.
     *
     * @return array
     */
    public function generateClickthrough()
    {
        $source       = $this->getSource();
        $email        = $this->getEmail();
        $clickthrough = [
            //what entity is sending the email?
            'source' => $source,
            //the email being sent to be logged in page hit if applicable
            'email' => (null != $email) ? $email->getId() : null,
            'stat'  => $this->getIdHash(),
        ];
        $lead = $this->getLead();
        if (null !== $lead) {
            $clickthrough['lead'] = $lead['id'];
        }

        return $clickthrough;
    }

    /**
     * Get the content hash to note if the content has been changed.
     *
     * @return string
     */
    public function getContentHash()
    {
        if (null !== $this->helper) {
            return $this->helper->getContentHash();
        } else {
            return md5($this->getContent().$this->getPlainText());
        }
    }

    /**
     * @return bool
     */
    public function isDynamicContentParsing()
    {
        return $this->isDynamicContentParsing;
    }
}
