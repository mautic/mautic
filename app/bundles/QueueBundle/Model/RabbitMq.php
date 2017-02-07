<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Model;

use Mautic\QueueBundle\Model\QueueName;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class RabbitMq
{

  public function __construct($rabbitmqEmail)
  {
    $this->rabbitmqEmail = $rabbitmqEmail;
  }

  /**
   * Add a message to queue
   * @param array $msg
   * @param String $queue_name
   */
  public function addMessageToQueue($msg, $queue_name)
  {
    switch ($queue_name) {
      case QueueName::Email:
        $this->rabbitmqEmail->publish($msg);
        break;
      case QueueName::Pixel:
        //TODO
        break;
      default:
        # code...
        break;
    }

  }

}
