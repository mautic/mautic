<?php

namespace Mautic\QueueBundle\Model;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Mautic\EmailBundle\Model\EmailModel;
use Monolog\Logger;

class Consumer implements ConsumerInterface
{
   private $model;

   public function __construct(EmailModel $model)
   {
        $this->model = $model;
        echo "Consumer is listening!" . PHP_EOL;
   }

   public function execute(AMQPMessage $msg)
   {
        echo "Begin processing " . PHP_EOL;
        $message = unserialize($msg->body);
        $request = $message['request'];
        $idHash = $message['idHash'];
        $this->model->hitEmail($idHash, $request);
        echo "End processing " . PHP_EOL;
        return true;
   }
}
