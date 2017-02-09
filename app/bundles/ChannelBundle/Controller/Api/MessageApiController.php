<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ChannelBundle\Entity\Message;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class MessageController.
 */
class MessageApiController extends CommonApiController
{
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('channel.message');
        $this->entityClass      = Message::class;
        $this->entityNameOne    = 'message';
        $this->entityNameMulti  = 'messages';
        $this->serializerGroups = ['messageDetails', 'messageChannelList', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    protected function prepareParametersFromRequest(Form $form, array &$params, $entity = null, $masks = [])
    {
        parent::prepareParametersFromRequest($form, $params, $entity, $masks);

        if ($this->request->getMethod() != 'PATCH') {
            $channels = $this->getModel('channel.message')->getChannels();
            if (!isset($params['channels'])) {
                $params['channels'] = [];
            }

            foreach ($channels as $channelType => $channel) {
                if (!isset($params['channels'][$channelType])) {
                    $params['channels'][$channelType] = [
                        'isEnabled' => 0,
                        'channel'   => $channelType,
                    ];
                } else {
                    $params['channels'][$channelType]['channel'] = $channelType;
                }
            }
        }
    }
}
