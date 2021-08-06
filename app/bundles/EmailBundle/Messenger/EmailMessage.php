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

class EmailMessage
{
    private \Swift_Message $mauticMessage;

    public function __construct(\Swift_Message $mauticMessage)
    {
        $this->mauticMessage = $mauticMessage;
    }

    public function getMauticMessage(): \Swift_Message
    {
        return $this->mauticMessage;
    }
}
