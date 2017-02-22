<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Event\TokenGenerateEvent;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
//            CitrixEvents::ON_CITRIX_TOKEN_GENERATE => ['onTokenGenerate', 254],
            EmailEvents::EMAIL_ON_BUILD => ['onEmailBuild', 0],
//            EmailEvents::EMAIL_ON_SEND => array('decodeTokensSend', 0),
            EmailEvents::EMAIL_ON_DISPLAY => ['decodeTokensDisplay', 0],
        ];
    }

    /**
     * @param TokenGenerateEvent $event
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onTokenGenerate(TokenGenerateEvent $event)
    {
        // inject product details in $event->params on email send
    }

    /**
     * @param EmailBuilderEvent $event
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $tokens         = [];
        $activeProducts = [];
        foreach (['meeting', 'training', 'assist'] as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[]         = $p;
                $tokens['{'.$p.'_button'] = $this->translator->trans('plugin.citrix.token.'.$p.'_button');
            }
        }
        if (0 === count($activeProducts)) {
            return;
        }

        // register tokens
        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
            );
        }
    }

    /**
     * Search and replace tokens with content.
     *
     * @param EmailSendEvent $event
     *
     * @throws \RuntimeException
     */
    public function decodeTokensDisplay(EmailSendEvent $event)
    {
        $this->decodeTokens($event);
    }

    /**
     * Search and replace tokens with content.
     *
     * @param EmailSendEvent $event
     *
     * @throws \RuntimeException
     */
    public function decodeTokensSend(EmailSendEvent $event)
    {
        $this->decodeTokens($event, true);
    }

    /**
     * Search and replace tokens with content.
     *
     * @param EmailSendEvent $event
     * @param bool           $triggerEvent
     *
     * @throws \RuntimeException
     */
    public function decodeTokens(EmailSendEvent $event, $triggerEvent = false)
    {
        // Get content
        $content = $event->getContent();

        // Search and replace tokens

        $products = [
            CitrixProducts::GOTOMEETING,
            CitrixProducts::GOTOTRAINING,
            CitrixProducts::GOTOASSIST,
        ];

        foreach ($products as $product) {
            if (CitrixHelper::isAuthorized('Goto'.$product)) {
                $params = [
                    'product' => $product,
                ];

                // trigger event to replace the links in the tokens
                if ($triggerEvent && $this->dispatcher->hasListeners(CitrixEvents::ON_CITRIX_TOKEN_GENERATE)) {
                    $tokenEvent = new TokenGenerateEvent($params);
                    $this->dispatcher->dispatch(CitrixEvents::ON_CITRIX_TOKEN_GENERATE, $tokenEvent);
                    $params = $tokenEvent->getParams();
                    unset($tokenEvent);
                }

                $button = $this->templating->render(
                    'MauticCitrixBundle:SubscribedEvents\EmailToken:token.html.php',
                    $params
                );
                $content = str_replace('{'.$product.'_button}', $button, $content);
            } else {
                // remove the token
                $content = str_replace('{'.$product.'_button}', '', $content);
            }
        }

        // Set updated content
        $event->setContent($content);
    }
}
