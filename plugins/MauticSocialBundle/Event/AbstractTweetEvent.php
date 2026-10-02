<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticSocialBundle\Entity\Tweet;

abstract class AbstractTweetEvent extends CommonEvent
{
    public function __construct(Tweet $tweet, bool $isNew = false)
    {
        $this->entity = $tweet;
        $this->isNew  = $isNew;
    }

    public function getTweet(): Tweet
    {
        return $this->entity;
    }
}
