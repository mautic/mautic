<?php

namespace Mautic\ChannelBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Entity\MessageRepository;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\ChannelBundle\Form\Type\MessageType;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<Message>
 *
 * @implements AjaxLookupModelInterface<Message>
 */
class MessageModel extends FormModel implements AjaxLookupModelInterface
{
    public const CHANNEL_FEATURE = 'marketing_messages';

    protected static $channels;

    public function __construct(
        protected ChannelListHelper $channelListHelper,
        protected CampaignModel $campaignModel,
        EntityManager $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @param Message $entity
     * @param bool    $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = $entity->isNew();

        parent::saveEntity($entity, $unlock);

        if (!$isNew) {
            // Update the channels
            $channels = $entity->getChannels();
            foreach ($channels as $channel) {
                $channel->setMessage($entity);
            }
            $this->getRepository()->saveEntities($channels);
        }
    }

    public function getPermissionBase(): string
    {
        return 'channel:messages';
    }

    public function getRepository(): ?MessageRepository
    {
        return $this->em->getRepository(\Mautic\ChannelBundle\Entity\Message::class);
    }

    public function getEntity($id = null): ?Message
    {
        if (null === $id) {
            return new Message();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object $entity
     * @param array  $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(MessageType::class, $entity, $options);
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        if (!self::$channels) {
            $channels = $this->channelListHelper->getFeatureChannels(self::CHANNEL_FEATURE);

            // Validate channel configs
            foreach ($channels as $channel => $config) {
                if (!isset($config['lookupFormType']) && !isset($config['propertiesFormType'])) {
                    throw new \InvalidArgumentException('lookupFormType and/or propertiesFormType are required for channel '.$channel);
                }

                $label = match (true) {
                    $this->translator->hasId('mautic.channel.'.$channel)      => $this->translator->trans('mautic.channel.'.$channel),
                    $this->translator->hasId('mautic.'.$channel)              => $this->translator->trans('mautic.'.$channel),
                    $this->translator->hasId('mautic.'.$channel.'.'.$channel) => $this->translator->trans('mautic.'.$channel.'.'.$channel),
                    default                                                   => ucfirst($channel),
                };
                $config['label'] = $label;

                $channels[$channel] = $config;
            }

            self::$channels = $channels;
        }

        return self::$channels;
    }

    /**
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = []): array
    {
        $results = [];
        switch ($type) {
            case 'channel.message':
                $entities = $this->getRepository()->getMessageList(
                    $filter,
                    $limit,
                    $start
                );

                foreach ($entities as $entity) {
                    $results[] = [
                        'label' => $entity['name'],
                        'value' => $entity['id'],
                    ];
                }

                break;
        }

        return $results;
    }

    public function getMessageChannels($messageId): array
    {
        return $this->getRepository()->getMessageChannels($messageId);
    }

    /**
     * @return array
     */
    public function getChannelMessageByChannelId($channelId)
    {
        return $this->getRepository()->getChannelMessageByChannelId($channelId);
    }

    public function getLeadStatsPost($messageId, $dateFrom = null, $dateTo = null, $channel = null): array
    {
        $eventLog = $this->campaignModel->getCampaignLeadEventLogRepository();

        return $eventLog->getChartQuery(
            [
                'type'       => 'message.send',
                'dateFrom'   => $dateFrom,
                'dateTo'     => $dateTo,
                'channel'    => 'channel.message',
                'channelId'  => $messageId,
                'logChannel' => $channel,
            ]
        );
    }

    /**
     * @return mixed
     */
    public function getMarketingMessagesEventLogs($messageId, $dateFrom = null, $dateTo = null)
    {
        $eventLog = $this->campaignModel->getCampaignLeadEventLogRepository();

        return $eventLog->getEventLogs(['type' => 'message.send', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'channel' => 'message', 'channelId' => $messageId]);
    }

    /**
     * Get the channel name from the database.
     *
     * @template T of object
     *
     * @param int             $id
     * @param class-string<T> $entityName
     * @param string          $nameColumn
     *
     * @return string|null
     */
    public function getChannelName($id, $entityName, $nameColumn = 'name')
    {
        if (!$id || !$entityName || !$nameColumn) {
            return null;
        }

        $repo = $this->em->getRepository($entityName);
        $qb   = $repo->createQueryBuilder('e')
            ->select('e.'.$nameColumn)
            ->where('e.id = :id')
            ->setParameter('id', (int) $id);
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result[$nameColumn] ?? null;
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof Message) {
            throw new MethodNotAllowedHttpException(['Message']);
        }

        switch ($action) {
            case 'pre_save':
                $name = ChannelEvents::MESSAGE_PRE_SAVE;
                break;
            case 'post_save':
                $name = ChannelEvents::MESSAGE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = ChannelEvents::MESSAGE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = ChannelEvents::MESSAGE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new MessageEvent($entity, $isNew);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
