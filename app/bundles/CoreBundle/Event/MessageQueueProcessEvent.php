<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

@trigger_error('Mautic\CoreBundle\Event\MessageQueueProcessEvent was deprecated in 2.4 and to be removed in 3.0 Use \Mautic\ChannelBundle\Event\MessageQueueProcessEvent instead', E_DEPRECATED);

/**
 * Class MessageQueueProcessEvent.
 *
 * @deprecated 2.4 to be removed in 3.0; use \Mautic\ChannelBundle\Event\MessageQueueEvent instead
 */
class MessageQueueProcessEvent extends \Mautic\ChannelBundle\Event\MessageQueueProcessEvent
{
}
