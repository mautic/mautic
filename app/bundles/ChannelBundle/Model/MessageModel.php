<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Model;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Entity\Message;
use Mautic\ChannelBundle\Event\MessageEvent;
use Mautic\ChannelBundle\Form\Type\MessageType;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class MessageModel.
 */
class MessageModel extends FormModel implements AjaxLookupModelInterface
{
    const CHANNEL_FEATURE = 'marketing_messages';

    /**
     * @var ChannelListHelper
     */
    protected $channelListHelper;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var
     */
    protected static $channels;

    /**
     * MessageModel constructor.
     *
     * @param ChannelListHelper $channelListHelper
     * @param CampaignModel     $campaignModel
     */
    public function __construct(ChannelListHelper $channelListHelper, CampaignModel $campaignModel)
    {
        $this->channelListHelper = $channelListHelper;
        $this->campaignModel     = $campaignModel;
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'channel:messages';
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\Mautic\ChannelBundle\Entity\MessageRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticChannelBundle:Message');
    }

    /**
     * @param null $id
     *
     * @return Form
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Message();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object      $entity
     * @param FormFactory $formFactory
     * @param null        $action
     * @param array       $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
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

                switch (true) {
                    case $this->translator->hasId('mautic.channel.'.$channel):
                        $label = $this->translator->trans('mautic.channel.'.$channel);
                        break;
                    case $this->translator->hasId('mautic.'.$channel):
                        $label = $this->translator->trans('mautic.'.$channel);
                        break;
                    case $this->translator->hasId('mautic.'.$channel.'.'.$channel):
                        $label = $this->translator->trans('mautic.'.$channel.'.'.$channel);
                        break;
                    default:
                        $label = ucfirst($channel);
                }
                $config['label'] = $label;

                $channels[$channel] = $config;
            }

            self::$channels = $channels;
        }

        return self::$channels;
    }

    /**
     * @param        $type
     * @param string $filter
     * @param int    $limit
     * @param int    $start
     * @param array  $options
     *
     * @return array
     */
    public function getLookupResults($type, $filter = '', $limit = 10, $start = 0, $options = [])
    {
        $results = [];
        switch ($type) {
            case 'channel.message':
                $entities = $this->getRepository()->getMessageList(
                    $filter,
                    $limit,
                    $start,
                    $this->security->isGranted($this->getPermissionBase().':viewother')
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

    /**
     * @param $messageId
     *
     * @return array
     */
    public function getMessageChannels($messageId)
    {
        return $this->getRepository()->getMessageChannels($messageId);
    }

    /**
     * @param $channelId
     *
     * @return array
     */
    public function getChannelMessageByChannelId($channelId)
    {
        return $this->getRepository()->getChannelMessageByChannelId($channelId);
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @param $options
     *
     * @return array
     */
    public function getLeadStatsPost($messageId, $dateFrom = null, $dateTo = null)
    {
        $eventLog = $this->campaignModel->getCampaignLeadEventLogRepository();

        return $eventLog->getChartQuery(['type' => 'message.send', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'channel' => 'message', 'channelId' => $messageId]);
    }

    public function getMarketingMessagesEventLogs($messageId, $dateFrom = null, $dateTo = null)
    {
        $eventLog = $this->campaignModel->getCampaignLeadEventLogRepository();

        return $eventLog->getEventLogs(['type' => 'message.send', 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'channel' => 'message', 'channelId' => $messageId]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
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
            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }
}
