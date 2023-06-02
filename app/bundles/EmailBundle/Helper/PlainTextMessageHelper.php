<?php

namespace Mautic\EmailBundle\Helper;

use Symfony\Component\Mime\Email;

/**
 * Class PlainTextMessageHelper.
 */
class PlainTextMessageHelper
{
    /**
     * Extract plain text from message.
     *
     * @return string
     */
    public static function getPlainTextFromMessage(Email $message)
    {
        return $message->getTextBody();
    }
}
