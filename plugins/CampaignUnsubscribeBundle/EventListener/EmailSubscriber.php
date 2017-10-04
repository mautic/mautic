<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\Routing\Router;

/**
 * Class EmailSubscriber
 * @package MauticPlugin\CampaignUnsubscribeBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    /**
     * Register the tokens
     *
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        // Add email tokens
        $event->addToken('{campaign_unsubscribe_link}', 'Campaign Unsubscribe Link');
        $event->addToken('{campaign_unsubscribe_url}', 'Campaign Unsubscribe Url');
    }

    /**
     * Search and replace tokens with content
     *
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {

        $idHash = $event->getIdHash();

        if ($idHash == null) {
            $idHash = uniqid();
        }

        $unsubLink = $this->router->generate('unsubscribe', ['idHash' => $idHash], Router::ABSOLUTE_URL);

        // Get content
        $content = $event->getContent();

        // Get templates
        $unsubElement = $this->templating->render('CampaignUnsubscribeBundle:EmailToken:token.html.php', ['link' => $unsubLink]);

        // Search and replace tokens
        $content = str_replace('{campaign_unsubscribe_link}', $unsubElement, $content);
        $content = str_replace('{campaign_unsubscribe_url}', $unsubLink, $content);

        // Set updated content
        $event->setContent($content);
    }

}