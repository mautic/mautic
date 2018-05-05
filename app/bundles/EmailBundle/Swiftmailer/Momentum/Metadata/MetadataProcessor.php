<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Metadata;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;

class MetadataProcessor
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $substitutionKeys = [];

    /**
     * @var array
     */
    private $substitutionMergeVars = [];

    /**
     * @var \Swift_Message
     */
    private $message;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $text;

    /**
     * MetadataProcessor constructor.
     *
     * @param \Swift_Message $message
     */
    public function __construct(\Swift_Message $message)
    {
        $this->message = $message;

        $metadata       = ($message instanceof MauticMessage) ? $message->getMetadata() : [];
        $this->metadata = $metadata;

        // Build the substitution merge vars
        $this->buildSubstitutionData();

        if (count($this->substitutionKeys)) {
            // Update the content with the substitution merge vars
            MailHelper::searchReplaceTokens($this->substitutionKeys, $this->substitutionMergeVars, $this->message);
        }
    }

    /**
     * @param $email
     *
     * @return array|mixed
     */
    public function getMetadata($email)
    {
        if (!isset($this->metadata[$email])) {
            return [];
        }

        $metadata = $this->metadata[$email];

        // remove the tokens as they'll be part of the substitution data
        unset($metadata['tokens']);

        return $metadata;
    }

    /**
     * @param $email
     *
     * @return array
     */
    public function getSubstitutionData($email)
    {
        if (!isset($this->metadata[$email])) {
            return [];
        }

        $substitutionData = [];
        foreach ($this->metadata[$email]['tokens'] as $token => $value) {
            $substitutionData[$this->substitutionMergeVars[$token]] = $value;
        }

        return $substitutionData;
    }

    private function buildSubstitutionData()
    {
        // Sparkpost uses {{ name }} for tokens so Mautic's need to be converted; although using their {{{ }}} syntax to prevent HTML escaping
        $metadataSet  = reset($this->metadata);
        $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
        $mauticTokens = array_keys($tokens);

        foreach ($mauticTokens as $token) {
            $this->substitutionKeys[$token]      = strtoupper(preg_replace('/[^a-z0-9]+/i', '', $token));
            $this->substitutionMergeVars[$token] = '{{{ '.$this->substitutionKeys[$token].' }}}';
        }
    }
}
