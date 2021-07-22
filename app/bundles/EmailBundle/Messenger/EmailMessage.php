<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Messenger;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;

class EmailMessage
{
    /**
     * @var \Swift_Message
     */
    private $mauticMessage;

    public function __construct(\Swift_Message $mauticMessage)
    {
        $this->mauticMessage = $mauticMessage;
    }

    /**
     * @return MauticMessage
     */
    public function getMauticMessage()
    {
        return $this->mauticMessage;
    }
}
