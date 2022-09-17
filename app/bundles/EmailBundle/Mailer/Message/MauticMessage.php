<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Mailer\Message;

use Symfony\Component\Mime\Email;

class MauticMessage extends Email
{
    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * Create a new Message.
     *
     * @param string $subject
     * @param string $body
     *
     * @return Email
     */
    public static function newInstance($subject = null, $body = null)
    {
        return (new Email())
            ->subject($subject)
            ->html($body);
    }

    /**
     * @param $email
     */
    public function addMetadata($email, array $metadata)
    {
        $this->metadata[$email] = $metadata;
    }

    /**
     * Get the metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Clears the metadata.
     */
    public function clearMetadata()
    {
        $this->metadata = [];
    }

    /**
     * @param            $filePath
     * @param null       $fileName
     * @param null       $contentType
     * @param bool|false $inline
     */
    public function addAttachment($filePath, $fileName = null, $contentType = null, $inline = false)
    {
        if (true === $inline) {
            $this->embedFromPath($filePath, $fileName, $contentType);

            return;
        }

        $this->attachFromPath($filePath, $fileName, $contentType);
    }
}
