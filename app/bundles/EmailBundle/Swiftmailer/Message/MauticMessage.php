<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Message;

use TDM\SwiftMailerEventBundle\Model\MessageMetadataInterface;

class MauticMessage extends \Swift_Message implements MessageMetadataInterface
{

    /**
     * @var array
     */
    protected $metadata   = array();

    /**
     * @var array
     */
    protected $attachments = array();

    /**
     * Create a new Message.
     *
     * @param string $subject
     * @param string $body
     * @param string $contentType
     * @param string $charset
     *
     * @return Swift_Message
     */
    public static function newInstance($subject = null, $body = null, $contentType = null, $charset = null)
    {
        return new self($subject, $body, $contentType, $charset);
    }

    /**
     * @param       $email
     * @param array $metadata
     */
    public function addMetadata($email, array $metadata)
    {
        $this->metadata[$email] = $metadata;
    }

    /**
     * @param $email
     * @param $key
     * @param $value
     */
    public function setMetadata($email, $key, $value)
    {
        $this->metadata[$email][$key] = $value;
    }

    /**
     * Check whether there is metadata.
     *
     * @return array
     */
    public function hasMetadata($email, $key = null)
    {
        return (
            !empty($this->metadata[$email])
            &&
            is_array($this->metadata[$email])
            &&
            (is_null($key) || isset($this->metadata[$email][$key]))
        );
    }

    /**
     * Get the metadata
     *
     * @return array
     */
    public function getMetadata($email = null, $key = null)
    {
        if (is_null($email)) {
            return $this->metadata;
        }
        elseif (is_null($key)) {
            return $this->metadata[$email];
        }
        else {
            return $this->metadata[$email][$key];
        }
    }

    /**
     * Clears the metadata
     */
    public function clearMetadata($email = null) {
        if (!is_null($email)) {
            unset($this->metadata[$email]);
        }
        else {
            $this->metadata = array();
        }
    }

    /**
     * @param            $filePath
     * @param null       $fileName
     * @param null       $contentType
     * @param bool|false $inline
     */
    public function addAttachment($filePath, $fileName = null, $contentType = null, $inline = false)
    {
        $attachment = array(
            'filePath'    => $filePath,
            'fileName'    => $fileName,
            'contentType' => $contentType,
            'inline'      => $inline
        );

        $this->attachments[] = $attachment;
    }

    /**
     * Get attachments
     *
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Clear attachments
     */
    public function clearAttachments()
    {
        $this->attachments = array();
    }
}