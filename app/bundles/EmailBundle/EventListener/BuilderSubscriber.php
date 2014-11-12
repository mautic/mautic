<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class BuilderSubscriber
 *
 * @package Mautic\EmailBundle\EventListener
 */
class BuilderSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    public function onEmailBuild(EmailBuilderEvent $event)
    {
        //add email tokens
        $content = $this->templating->render('MauticEmailBundle:SubscribedEvents\EmailToken:token.html.php');
        $event->addTokenSection('email.emailtokens', 'mautic.email.header.index', $content);

        //add AB Test Winner Criteria
        $openRate = array(
            'group'    => 'mautic.email.abtest.criteria',
            'label'    => 'mautic.email.abtest.criteria.open',
            'callback' => '\Mautic\EmailBundle\Helper\AbTestHelper::determineOpenRateWinner'
        );
        $event->addAbTestWinnerCriteria('email.openrate', $openRate);
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content  = $event->getContent();
        $idHash   = $event->getIdHash();
        $router   = $this->factory->getRouter();

        foreach ($content as $slot => &$html) {
            if (strpos($html, '{unsubscribe_text}') !== false) {
                $html = str_ireplace('{unsubscribe_text}',
                    $this->translator->trans('mautic.email.unsubscribe.text', array(
                        '%link%' => $router->generate('mautic_email_unsubscribe', array('idHash' => $idHash), true)
                    ))
                , $html);
            }

            if (strpos($html, '{unsubscribe_url}') !== false) {
                $html = str_ireplace('{unsubscribe_url}',
                    $router->generate('mautic_email_unsubscribe', array('idHash' => $idHash), true)
                , $html);
            }

            if (strpos($html, '{webview_text}') !== false) {
                $html = str_ireplace('{webview_text}',
                    $this->translator->trans('mautic.email.webview.text', array(
                        '%link%' => $router->generate('mautic_email_webview', array('idHash' => $idHash), true)
                    ))
                    , $html);
            }

            if (strpos($html, '{webview_url}') !== false) {
                $html = str_ireplace('{webview_url}',
                    $router->generate('mautic_email_webview', array('idHash' => $idHash), true)
                    , $html);
            }
        }

        $event->setContent($content);
    }
}