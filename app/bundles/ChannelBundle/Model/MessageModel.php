<?php

namespace Mautic\ChannelBundle\Model;

use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Entity\MessageRepository;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\ChannelBundle\Form\Type\MessageType;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends FormModel<Message>
 *
 * @implements AjaxLookupModelInterface<Message>
 */
class MessageModel extends FormModel implements AjaxLookupModelInterface, GlobalSearchInterface
{
    public static function getName(): string
    {
        return 'channel.message';
    }

    public const CHANNEL_FEATURE = 'marketing_messages';

    protected static $channels;

    protected ChannelListHelper $channelListHelper;

    private LeadEventLogRepository $leadEventLogRepository;

    private MessageRepository $messageRepository;

    #[Required]
    public function autowireMessageModel(
        ChannelListHelper $channelListHelper,
        LeadEventLogRepository $leadEventLogRepository,
        MessageRepository $messageRepository,
    ): void {
        $this->channelListHelper      = $channelListHelper;
        $this->leadEventLogRepository = $leadEventLogRepository;
        $this->messageRepository      = $messageRepository;
    }

    /**
     * @param Message $entity
     */
    public function saveEntity($entity, bool $unlock = true): void
    {
        $isNew = $entity->isNew();

        parent::saveEntity($entity, $unlock);

        if (!$isNew) {
            // Update the channels
            $channels = $entity->getChannels();
            foreach ($channels as $channel) {
                $channel->setMessage($entity);
            }
            $this->messageRepository->saveEntities($channels);
        }
    }

    public function getPermissionBase(): string
    {
        return 'channel:messages';
    }

    public function getRepository(): MessageRepository
    {
        return $this->messageRepository;
    }

    public function getEntity($id = null): ?Message
    {
        if (null === $id) {
            return new Message();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object  $entity
     * @param mixed[] $options
     *
     * @return FormInterface<mixed>
     */
    public function createForm($entity, $action = null, $options = []): FormInterface
    {
        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(MessageType::class, $entity, $options);
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

                $labelKeys = [
                    'mautic.channel.'.$channel,
                    'mautic.'.$channel,
                    'mautic.'.$channel.'.'.$channel,
                ];

                $label = ucfirst($channel);
                foreach ($labelKeys as $labelKey) {
                    $translation = $this->translator->trans($labelKey);
                    if ($translation !== $labelKey) {
                        $label = $translation;
                        break;
                    }
                }

                $config['label'] = $label;

                $channels[$channel] = $config;
            }

            self::$channels = $channels;
        }

        return self::$channels;
    }

    public function getLookupResults(string $type, string|array $filter = '', int $limit = 10, int $start = 0, array $options = []): array
    {
        $results = [];
        if ('channel.message' === $type) {
            $entities = $this->messageRepository->getMessageList(
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
        }

        return $results;
    }

    public function getMessageChannels($messageId): array
    {
        return $this->messageRepository->getMessageChannels($messageId);
    }

    /**
     * @return array
     */
    public function getChannelMessageByChannelId($channelId)
    {
        return $this->messageRepository->getChannelMessageByChannelId($channelId);
    }

    public function getLeadStatsPost($messageId, $dateFrom = null, $dateTo = null, $channel = null): array
    {
        return $this->leadEventLogRepository->getChartQuery(
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
        return $this->leadEventLogRepository->getEventLogs(['type' => 'message.send', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'channel' => 'message', 'channelId' => $messageId]);
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
    protected function dispatchEvent($action, &$entity, bool $isNew = false, ?Event $event = null): ?Event
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
            if (!$event instanceof Event) {
                $event = new MessageEvent($entity, $isNew);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
