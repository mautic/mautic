<?php

namespace Mautic\ChannelBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Message>
 */
class MessageApiController extends CommonApiController
{
    /**
     * @var MessageModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
    ) {
        $messageModel = $modelFactory->getModel('channel.message');
        \assert($messageModel instanceof MessageModel);
        $this->model            = $messageModel;
        $this->entityClass      = Message::class;
        $this->entityNameOne    = 'message';
        $this->entityNameMulti  = 'messages';
        $this->serializerGroups = ['messageDetails', 'messageChannelList', 'categoryList', 'publishDetails'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    protected function prepareParametersFromRequest(FormInterface $form, array &$params, object $entity = null, array $masks = [], array $fields = []): void
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
