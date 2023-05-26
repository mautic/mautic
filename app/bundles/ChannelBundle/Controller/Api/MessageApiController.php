<?php

namespace Mautic\ChannelBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Message>
 */
class MessageApiController extends CommonApiController
{
    /**
     * @var MessageModel|null
     */
    protected $model = null;

    private RequestStack $requestStack;

    public function __construct(CorePermissions $security, Translator $translator, EntityResultHelper $entityResultHelper, RouterInterface $router, FormFactoryInterface $formFactory, AppVersion $appVersion, RequestStack $requestStack, ManagerRegistry $doctrine)
    {
        $this->requestStack = $requestStack;
        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine);
    }

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

        if ('PATCH' === $this->requestStack->getCurrentRequest()->getMethod() && !isset($params['channels'])) {
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
