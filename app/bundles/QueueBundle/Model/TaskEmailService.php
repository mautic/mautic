<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Model;

use Mautic\QueueBundle\Helper\CoreParametersHelper;
use Mautic\QueueBundle\Model\RabbitMq;
use Mautic\QueueBundle\Model\QueueProtocol;


// register here the different types of queue protocols
abstract class QueueProtocol
{
    const RabbitMq = 0;
    // just as an example
    const Beanstalkd = 1;
}

// register here the different types of queues
abstract class QueueName
{
    const Email = 0;
    // just an example
    const Pixel = 1;
}

class TaskEmailService
{

  public function __construct($queueParametersHelper, $rabbitmq)
  {
    $this->queueParametersHelper = $queueParametersHelper;
    $this->rabbitmq = $rabbitmq;
  }

  /**
   * Add a message to queue
   * @param array $msg
   * @param String $queue_name
   */
  public function addMessageToQueue($msg, $queue_name)
  {
    // queue_name is not used right now
    $queue_protocol = $this->queueParametersHelper->getParameter('queue_protocol');
    switch ($queue_protocol) {
      case QueueProtocol::RabbitMq:
          $this->rabbitmq->addMessageToQueue($msg, $queue_name);
          break;
      case QueueProtocol::Beanstalkd:
          // TODO
          break;
      default:
          // TODO
        break;
    }
  }

}
