<?php

namespace MauticPlugin\MauticSocialBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use MauticPlugin\MauticSocialBundle\Entity\Tweet;
use MauticPlugin\MauticSocialBundle\Model\TweetModel;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Tweet>
 */
class TweetApiController extends CommonApiController
{
    /**
     * @var TweetModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $tweetModel = $this->getModel('social.tweet');

        if (!$tweetModel instanceof TweetModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model           = $tweetModel;
        $this->entityClass     = Tweet::class;
        $this->entityNameOne   = 'tweet';
        $this->entityNameMulti = 'tweets';

        parent::initialize($event);
    }
}
