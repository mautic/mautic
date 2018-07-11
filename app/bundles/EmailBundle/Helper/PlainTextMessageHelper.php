<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

/**
 * Class PlainTextMessageHelper.
 */
class PlainTextMessageHelper
{
    /**
     * Extract plain text from message.
     *
     * @param \Swift_Mime_Message $message
     *
     * @return string
     */
    public static function getPlainTextFromMessage(\Swift_Mime_Message $message)
    {
        $children = (array) $message->getChildren();

        foreach ($children as $child) {
            $childType = $child->getContentType();
            if ($childType === 'text/plain' && $child instanceof \Swift_MimePart) {
                return $child->getBody();
            }
        }

        return '';
    }

    /**
     * Extract plain text from message.
     *
     * @param \Swift_Mime_Message $message
     *
     * @return string
     */
    public function getPlainTextFromMessageNotStatic(\Swift_Mime_Message $message)
    {
        return self::getPlainTextFromMessage($message);
    }
}
