<?php

namespace MauticPlugin\MauticSocialBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;

/**
 * Class TweetApiController.
 */
class TweetApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(\Symfony\Component\HttpKernel\Event\ControllerEvent $event)
    {
        $this->model           = $this->getModel('social.tweet');
        $this->entityClass     = 'MauticPlugin\MauticSocialBundle\Entity\Tweet';
        $this->entityNameOne   = 'tweet';
        $this->entityNameMulti = 'tweets';

        parent::initialize($event);
    }
}
