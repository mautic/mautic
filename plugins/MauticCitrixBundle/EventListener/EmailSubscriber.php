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

use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;
use MauticPlugin\MauticCitrixBundle\Event\TokenGenerateEvent;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use MauticPlugin\MauticCitrixBundle\Model\CitrixModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var CitrixModel
     */
    private $citrixModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    public function __construct(
        CitrixModel $citrixModel,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher,
        TemplatingHelper $templating
    ) {
        $this->citrixModel = $citrixModel;
        $this->translator  = $translator;
        $this->dispatcher  = $dispatcher;
        $this->templating  = $templating;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CitrixEvents::ON_CITRIX_TOKEN_GENERATE => ['onTokenGenerate', 254],
            EmailEvents::EMAIL_ON_BUILD            => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND             => ['decodeTokensSend', 0],
            EmailEvents::EMAIL_ON_DISPLAY          => ['decodeTokensDisplay', 0],
        ];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onTokenGenerate(TokenGenerateEvent $event)
    {
        // inject product details in $event->params on email send
        if ('webinar' == $event->getProduct()) {
            $event->setProductLink('https://www.gotomeeting.com/webinar');
            $params = $event->getParams();
            if (!empty($params['lead'])) {
                $email  = $params['lead']['email'];
                $repo   = $this->citrixModel->getRepository();
                $result = $repo->findBy(
                    [
                        'product'   => 'webinar',
                        'eventType' => 'registered',
                        'email'     => $email,
                    ], ['eventDate' => 'DESC'], 1);
                if (0 !== count($result)) {
                    /** @var CitrixEvent $ce */
                    $ce = $result[0];
                    $event->setProductLink($ce->getJoinUrl());
                }
            } else {
                CitrixHelper::log('Updating webinar token failed! Email not found '.implode(', ', $event->getParams()));
            }
            $event->setProductText($this->translator->trans('plugin.citrix.token.join_webinar'));
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        // register tokens only if the plugins are enabled
        $tokens         = [];
        $activeProducts = [];
        foreach (['meeting', 'training', 'assist', 'webinar'] as $p) {
            if (CitrixHelper::isAuthorized('Goto'.$p)) {
                $activeProducts[]          = $p;
                $tokens['{'.$p.'_button}'] = $this->translator->trans('plugin.citrix.token.'.$p.'_button');
                if ('webinar' === $p) {
                    $tokens['{'.$p.'_link}'] = $this->translator->trans('plugin.citrix.token.'.$p.'_link');
                }
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
     * @throws \RuntimeException
     */
    public function decodeTokensDisplay(EmailSendEvent $event)
    {
        $this->decodeTokens($event, false);
    }

    /**
     * Search and replace tokens with content.
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
     * @param bool $triggerEvent
     *
     * @throws \RuntimeException
     */
    public function decodeTokens(EmailSendEvent $event, $triggerEvent = false)
    {
        $products = [
            CitrixProducts::GOTOMEETING,
            CitrixProducts::GOTOTRAINING,
            CitrixProducts::GOTOASSIST,
            CitrixProducts::GOTOWEBINAR,
        ];

        $tokens = [];
        foreach ($products as $product) {
            if (CitrixHelper::isAuthorized('Goto'.$product)) {
                $params = [
                    'product'     => $product,
                    'productText' => '',
                    'productLink' => '',
                ];

                if ('webinar' == $product) {
                    $params['productText'] = $this->translator->trans('plugin.citrix.token.join_webinar');
                    $params['productLink'] = 'https://www.gotomeeting.com/webinar';
                }

                // trigger event to replace the links in the tokens
                if ($triggerEvent && $this->dispatcher->hasListeners(CitrixEvents::ON_CITRIX_TOKEN_GENERATE)) {
                    $params['lead'] = $event->getLead();
                    $tokenEvent     = new TokenGenerateEvent($params);
                    $this->dispatcher->dispatch(CitrixEvents::ON_CITRIX_TOKEN_GENERATE, $tokenEvent);
                    $params = $tokenEvent->getParams();
                    unset($tokenEvent);
                }

                $button = $this->templating->getTemplating()->render(
                    'MauticCitrixBundle:SubscribedEvents\EmailToken:token.html.php',
                    $params
                );

                $tokens['{'.$product.'_link}']   = $params['productLink'];
                $tokens['{'.$product.'_button}'] = $button;
            }
        }
        $event->addTokens($tokens);
    }
}
