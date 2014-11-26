<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        $event->addTokenSection('email.emailtokens', 'mautic.email.builder.index', $content);

        //add AB Test Winner Criteria
        $openRate = array(
            'group'    => 'mautic.email.abtest.criteria',
            'label'    => 'mautic.email.abtest.criteria.open',
            'callback' => '\Mautic\EmailBundle\Helper\AbTestHelper::determineOpenRateWinner'
        );
        $event->addAbTestWinnerCriteria('email.openrate', $openRate);

        $clickThrough = array(
            'group'    => 'mautic.email.abtest.criteria',
            'label'    => 'mautic.email.abtest.criteria.clickthrough',
            'callback' => '\Mautic\EmailBundle\Helper\AbTestHelper::determineClickthroughRateWinner'
        );
        $event->addAbTestWinnerCriteria('email.clickthrough', $clickThrough);
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content  = $event->getContent();
        $idHash   = $event->getIdHash();
        $router   = $this->factory->getRouter();

        if (strpos($content, '{unsubscribe_text}') !== false) {
            $content = str_ireplace('{unsubscribe_text}',
                $this->translator->trans('mautic.email.unsubscribe.text', array(
                    '%link%' => $router->generate('mautic_email_unsubscribe', array('idHash' => $idHash), true)
                ))
            , $content);
        }

        if (strpos($content, '{unsubscribe_url}') !== false) {
            $content = str_ireplace('{unsubscribe_url}',
                $router->generate('mautic_email_unsubscribe', array('idHash' => $idHash), true)
            , $content);
        }

        if (strpos($content, '{webview_text}') !== false) {
            $content = str_ireplace('{webview_text}',
                $this->translator->trans('mautic.email.webview.text', array(
                    '%link%' => $router->generate('mautic_email_webview', array('idHash' => $idHash), true)
                ))
                , $content);
        }

        if (strpos($content, '{webview_url}') !== false) {
            $content = str_ireplace('{webview_url}',
                $router->generate('mautic_email_webview', array('idHash' => $idHash), true)
                , $content);
        }

        $event->setContent($content);
    }
}