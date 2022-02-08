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

    /**
     * Extract plain text from message.
     *
     * @return string
     */
    public function getPlainTextFromMessageNotStatic(Email $message)
    {
        return self::getPlainTextFromMessage($message);
    }
}
