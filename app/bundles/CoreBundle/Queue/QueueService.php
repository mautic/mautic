<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Queue;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Queue\RabbitMq;
use Mautic\CoreBundle\Queue\QueueProtocol;


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

class QueueService
{

  public function __construct($coreParametersHelper, $rabbitmq)
  {
    $this->coreParametersHelper = $coreParametersHelper;
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
    $queue_protocol = $this->coreParametersHelper->getParameter('queue_protocol');
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
