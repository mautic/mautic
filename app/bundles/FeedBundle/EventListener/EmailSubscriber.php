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
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\FeedBundle\Entity\Feed;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\FeedBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

    private static $leadFieldRegex = '{feedfield=(.*?)}';


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
        if ($event->tokensRequested(self::$leadFieldRegex)) {
            $event->addTokens(array(
                '{feedfield=title}' => 'Feed Title',
                '{feedfield=description}' => 'Feed Description',
                '{feedfield=link}' => 'Feed Link',
                '{feedfield=date}' => 'Feed Date',
                '{feedfield=id}' => 'Feed Public ID'
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
        $content = $event->getSubject()
                 . $event->getContent()
                 . $event->getPlainText();

        $feed = $event->getFeed();

        $tokenList = self::findFeedTokens($content, $feed);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }

    }

    /**
     * @param string $content
     * @param Feed   $feed
     * @param bool   $replace If true, search/replace will be executed on $content and the modified $content returned
     *                        rather than an array of found matches
     * @param MauticFactory $factory
     *
     * @return array|string
     */
    static function findFeedTokens($content, $feed, $replace = false)
    {
        // Search for bracket or bracket encoded
        $regex     = '/({|%7B)feedfield=(.*?)(}|%7D)/';
        $tokenList = array();

        $foundMatches = preg_match_all($regex, $content, $matches);
        if ($foundMatches) {
            foreach ($matches[2] as $key => $match) {
                $token = $matches[0][$key];

                if (isset($tokenList[$token])) {
                    continue;
                }

                $fallbackCheck = explode('|', $match);
                $urlencode     = false;
                $fallback      = '';

                if (isset($fallbackCheck[1])) {
                    // There is a fallback or to be urlencoded
                    $alias = $fallbackCheck[0];

                    if ($fallbackCheck[1] === 'true') {
                        $urlencode = true;
                        $fallback  = '';
                    } else {
                        $fallback = $fallbackCheck[1];
                    }
                } else {
                    $alias = $match;
                }

                $value             = (!empty($feed[$alias])) ? $feed[$alias] : $fallback;
                $tokenList[$token] = ($urlencode) ? urlencode($value) : $value;
            }

            if ($replace) {
                $content = str_replace(array_keys($tokenList), $tokenList, $content);
            }
        }

        return $replace ? $content : $tokenList;
    }

}
