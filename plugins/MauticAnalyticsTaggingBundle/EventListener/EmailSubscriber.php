<?php

namespace MauticPlugin\MauticAnalyticsTaggingBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class EmailSubscriber
 */
class EmailSubscriber extends CommonSubscriber {

    /**
     * @return array
     */
    static public function getSubscribedEvents() {
        return array(
            EmailEvents::EMAIL_ON_BUILD => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    /**
     * Register the tokens and a custom A/B test winner
     *
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event) {
        // Add email tokens
        //   $content = $this->templating->render('MauticAnalyticsTaggingBundle:SubscribedEvents\EmailToken:token.html.php');
        // $event->addTokenSection('extendedplugin.token', 'plugin.extendedplugin.header', $content);
    }

    /**
     * Search and replace tokens with content
     *
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event) {
        // Get content
        $content = $event->getContent();
        $email = $event->getEmail();
        if (empty($email))
            return;
        $email_id = $email->getId();

        $content = str_replace('{extendedplugin}', 'world!', $content);
        $utm_campaign = $utm_source = $this->factory->getParameter('utm_source');
        $utm_medium = $this->factory->getParameter('utm_medium');
        $utm_campaign_type = $this->factory->getParameter('utm_campaign');
        $remove_accents = $this->factory->getParameter('remove_accents');

        if ($utm_campaign_type == 'name')
            $utm_campaign = $email->getName();
        elseif ($utm_campaign_type == 'subject')
            $utm_campaign = $email->getSubject();

        if ($remove_accents) {
            $charset = 'utf-8';
            $utm_campaign_type = htmlentities($utm_campaign, ENT_NOQUOTES, $charset);

            $utm_campaign = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $utm_campaign);
            $utm_campaign = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $utm_campaign); // pour les ligatures e.g. '&oelig;'
            $utm_campaign = preg_replace('#&[^;]+;#', '', $utm_campaign); // supprime les autres caractères
        }

        $content = $this->add_analytics_tracking_to_urls($content, $utm_source, $utm_campaign, $utm_medium);
        $event->setContent($content);
    }

    protected function add_analytics_tracking_to_urls($body, $source, $campaign, $medium = 'email') {
        return preg_replace_callback('#(<a.*?href=")([^"]*)("[^>]*?>)#i', function($match) use ($source, $campaign, $medium) {
            $url = $match[2];
            if (strpos($url, 'utm_source') === false && strpos($url, 'http') !== false) {

                $add_to_url = '';
                if (strpos($url, '#') !== false) {
                    $url_array = explode("#", $url);
                    if (count($url_array) == 2) {
                        $url = $url_array[0];
                        $add_to_url = '#'.$url_array[1];
                    }
                }

                if (strpos($url, '?') === false) {
                    $url .= '?';
                } else {
                    $url .= '&';
                }
                $url .= 'utm_source=' . $source . '&utm_medium=' . $medium . '&utm_campaign=' . urlencode($campaign);
                $url .=$add_to_url;
            }
            return $match[1] . $url . $match[3];
        }, $body);
    }

}
