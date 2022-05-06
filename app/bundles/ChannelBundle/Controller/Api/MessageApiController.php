<?php

namespace Mautic\ChannelBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class MessageApiController extends CommonApiController
{
    /**
     * @var MessageModel|AbstractCommonModel
     */
    protected $model;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('channel.message');
        $this->entityClass      = Message::class;
        $this->entityNameOne    = 'message';
        $this->entityNameMulti  = 'messages';
        $this->serializerGroups = ['messageDetails', 'messageChannelList', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    protected function prepareParametersFromRequest(Form $form, array &$params, $entity = null, $masks = [], $fields = [])
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
     *
     * @param string $action
     *
     * @return mixed
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $event = $this->dispatcher->dispatch(ChannelEvents::ADD_CHANNEL, new ChannelEvent());

        if ($channels = $entity->getChannels()) {
            foreach ($channels as $channel) {
                $repository = $event->getRepositoryName($channel->getChannel());
                $nameColumn = $event->getNameColumn($channel->getChannel());
                $name       = $this->model->getChannelName($channel->getChannelId(), $repository, $nameColumn);
                $channel->setChannelName($name);
            }
        }
    }
}
