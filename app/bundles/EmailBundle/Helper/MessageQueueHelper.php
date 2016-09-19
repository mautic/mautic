<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Model\MessageQueueModel;

class MessageQueueHelper
{
    static $messageQueueModel;

    public function __construct(MessageQueueModel $messageQueueModel)
    {
        self::$messageQueueModel = $messageQueueModel;
    }


    public static function addToQueue($sendTo, $campaignId,$channel, $channelId, $emailAttempts, $emailPriority)
    {
        self::$messageQueueModel->addToQueue($sendTo, $campaignId,$channel, $channelId, $emailAttempts, $emailPriority);
    }

    public static function rescheduleMessage($leadId,$channel, $channelId, $rescheduleTime)
    {
        self::$messageQueueModel->addToQueue($leadId,$channel, $channelId, $rescheduleTime);
    }

}