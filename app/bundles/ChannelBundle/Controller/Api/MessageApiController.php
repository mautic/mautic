<?php

namespace Mautic\ChannelBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Message>
 */
class MessageApiController extends CommonApiController
{
    /**
     * @var MessageModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $messageModel = $this->getModel('channel.message');
        \assert($messageModel instanceof MessageModel);
        $this->model            = $messageModel;
        $this->entityClass      = Message::class;
        $this->entityNameOne    = 'message';
        $this->entityNameMulti  = 'messages';
        $this->serializerGroups = ['messageDetails', 'messageChannelList', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    protected function prepareParametersFromRequest(Form $form, array &$params, object $entity = null, array $masks = [], array $fields = []): void
    {
        parent::prepareParametersFromRequest($form, $params, $entity, $masks);

        if ('PATCH' === $this->request->getMethod() && !isset($params['channels'])) {
            return;
        } elseif (!isset($params['channels'])) {
            $params['channels'] = [];
        }

        $channels = $this->model->getChannels();

        foreach ($channels as $channelType => $channel) {
            if (!isset($params['channels'][$channelType])) {
                $params['channels'][$channelType] = ['isEnabled' => 0];
            } else {
                $params['channels'][$channelType]['isEnabled'] = (int) $params['channels'][$channelType]['isEnabled'];
            }
            $params['channels'][$channelType]['channel'] = $channelType;
        }
    }

    /**
     * Load and set channel names to the response.
     */
    protected function preSerializeEntity(object $entity, string $action = 'view'): void
    {
        $event = $this->dispatcher->dispatch(new ChannelEvent(), ChannelEvents::ADD_CHANNEL);

        foreach ($entity->getChannels() as $channel) {
            $repository = $event->getRepositoryName($channel->getChannel());
            $nameColumn = $event->getNameColumn($channel->getChannel());
            $name       = $this->model->getChannelName($channel->getChannelId(), $repository, $nameColumn);
            $channel->setChannelName($name);
        }
    }
}
