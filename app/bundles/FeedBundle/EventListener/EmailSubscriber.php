<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Webmecanik
 * @link        http://webmecanik.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FeedBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Token\DeprecatedTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\FeedBundle\Entity\Feed;
use Mautic\FeedBundle\Helper\FeedHelper;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\FeedBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

    /**
     * @var DeprecatedTokenHelper
     */
    protected $tokenHelper;

    /**
     * @var FeedHelper
     */
    protected $feedHelper;

    private static $feedFieldPrefix = 'feedfield';
    private static $feedFieldRegex = '{feedfield=(.*?)}';
    private static $feeditemsRegex = '{feed=(loopstart|loopend)}';

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailDisplay', 0)
        );
    }

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        if ($event->tokensRequested(self::$feedFieldRegex)) {
            $event->addTokens(array_flip(FeedHelper::$feedItems));
        }
        if ($event->tokensRequested(self::$feeditemsRegex)) {
            $event->addTokens(array_flip(FeedHelper::$feedLoopAction));
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event) {
        $this->onEmailGenerate($event);
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event) {

        $feed = $event->getFeed();

        if ($feed !== null) {

            $event->setContent($this->feedHelper->unfoldFeedItems($feed, $event->getContent()));

            $content = $event->getContent();

            $flatFeed = $this->feedHelper->flattenFeed($feed);

            $tokenList = $this->tokenHelper->findTokens(self::$feedFieldPrefix, $content, $flatFeed);

            if (count($tokenList)) {
                $event->addTokens($tokenList);
                unset($tokenList);
            }

        }

    }

    /**
     * @param FeedHelper $feedHelper
     */
    public function setFeedHelper(FeedHelper $feedHelper)
    {
        $this->feedHelper = $feedHelper;
        return $this;
    }

    /**
     * @param DeprecatedTokenHelper $tokenHelper
     */
    public function setTokenHelper(DeprecatedTokenHelper $tokenHelper)
    {
        $this->tokenHelper = $tokenHelper;
        return $this;
    }

}
