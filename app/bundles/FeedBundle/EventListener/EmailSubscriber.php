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
            EmailEvents::EMAIL_ON_BUILD => array('onEmailBuild', 0)
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
                '{feedfield=date}' => 'Feed Date',
                '{feedfield=url}' => 'Feed URL',
                '{feedfield=description}' => 'Feed Description',
            ));
        }
    }

}
