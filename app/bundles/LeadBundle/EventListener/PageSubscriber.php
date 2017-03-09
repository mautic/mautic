<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Event\UntrackableUrlsEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::REDIRECT_DO_NOT_TRACK => ['onRedirectDoNotTrack', 0],
        ];
    }

    /**
     * @param UntrackableUrlsEvent $event
     */
    public function onRedirectDoNotTrack(UntrackableUrlsEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'lead.field', 'lead:fields', 'MauticLeadBundle');
        $tokens      = $tokenHelper->getTokens();

        if ($event->tokensRequested(self::$leadFieldRegex)) {
            $event->addTokensFromHelper($tokenHelper, self::$leadFieldRegex, 'label', 'alias', true);
        }

        if ($event->tokensRequested(self::$contactFieldRegex)) {
            $event->addTokensFromHelper($tokenHelper, self::$contactFieldRegex, 'label', 'alias', true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event)
    {
        $this->onEmailGenerate($event);
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        // Combine all possible content to find tokens across them
        $content = $event->getSubject();
        $content .= $event->getContent();
        $content .= $event->getPlainText();
        $lead = $event->getLead();
        $this->factory->getLogger()->addError(print_r($content));
        $tokenList = TokenHelper::findLeadTokens($content, $lead);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }
}
