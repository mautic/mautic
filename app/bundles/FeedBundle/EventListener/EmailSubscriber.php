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
use Mautic\CoreBundle\Token\TokenHelper;
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
     * @var TokenHelper
     */
    protected $tokenHelper;

    /**
     * @var FeedHelper
     */
    protected $feedHelper;

    private static $feedFieldPrefix = 'feedfield';
    private static $itemFieldPrefix = 'itemfield';
    private static $feedFieldRegex  = '{feedfield=(.*?)}';
    private static $feeditemsRegex  = '{feeditems#(start|end)}';
    private static $itemFieldRegex  = '{itemfield=(.*?)}';

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD => array('onEmailBuild', 0),
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
            $event->addTokens(array(
                '{feedfield=title}' => 'Feed Title',
                '{feedfield=description}' => 'Feed Description',
                '{feedfield=link}' => 'Feed Link',
                '{feedfield=date}' => 'Feed Date'
            ));
        }
        if ($event->tokensRequested(self::$feeditemsRegex)) {
            $event->addTokens(array(
                '{feeditems#start}' => 'Start looping through the items',
                '{feeditems#end}' => 'Stop looping through the items'
            ));
        }
        if ($event->tokensRequested(self::$itemFieldRegex)) {
            $event->addTokens(array(
                '{itemfield=title}' => 'Item Title',
                '{itemfield=description}' => 'Item Description',
                '{itemfield=author}' => 'Item Author',
                '{itemfield=summary}' => 'Item Summary',
                '{itemfield=link}' => 'Item Link',
                '{itemfield=date}' => 'Item Date'
            ));
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

        $event->setContent($this->feedHelper->unfoldFeedItems($feed, $event->getContent()));

        $content = $event->getContent();

        $fieldTokenList = $this->tokenHelper->findTokens(self::$feedFieldPrefix, $content, $feed);

        $items = $this->feedHelper->flattenItems($feed['items']);

        $itemTokenList = $this->tokenHelper->findTokens(self::$itemFieldPrefix, $content, $items);

        $tokenList = array_merge($fieldTokenList, $itemTokenList);

        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
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
     * @param TokenHelper $tokenHelper
     */
    public function setTokenHelper(TokenHelper $tokenHelper)
    {
        $this->tokenHelper = $tokenHelper;
        return $this;
    }



}
