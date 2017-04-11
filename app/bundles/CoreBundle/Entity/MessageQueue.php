<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * Class MessageQueue.
 *
 * @deprecated 2.4 to be removed in 3.0; use \Mautic\ChannelBundle\Entity\MessageQueue instead
 */
class MessageQueue extends \Mautic\ChannelBundle\Entity\MessageQueue implements DeprecatedInterface
{
    public function __construct()
    {
        @trigger_error('Mautic\CoreBundle\Entity\MessageQueue was deprecated in 2.4 and to be removed in 3.0 Use \Mautic\ChannelBundle\Entity\MessageQueue instead', E_USER_DEPRECATED);
    }
}
