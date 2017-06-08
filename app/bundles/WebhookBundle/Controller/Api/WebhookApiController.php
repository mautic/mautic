<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class WebhookApiController.
 */
class WebhookApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('webhook');
        $this->entityClass      = Webhook::class;
        $this->entityNameOne    = 'hook';
        $this->entityNameMulti  = 'hooks';
        $this->serializerGroups = ['hookDetails', 'eventList', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    public function getEventsAction()
    {
        return $this->handleView(
            $this->view(
                [
                    'events' => $this->model->getEvents(),
                ]
            )
        );
    }
}
