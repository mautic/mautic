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

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        if ($event->tokenSectionsRequested()) {
            //add email tokens
            $content = $this->templating->render('MauticEmailBundle:SubscribedEvents\EmailToken:token.html.php');
            $event->addTokenSection('email.emailtokens', 'mautic.email.builder.index', $content);
        }

        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $openRate = array(
                'group'    => 'mautic.email.stats',
                'label'    => 'mautic.email.abtest.criteria.open',
                'callback' => '\Mautic\EmailBundle\Helper\AbTestHelper::determineOpenRateWinner'
            );
            $event->addAbTestWinnerCriteria('email.openrate', $openRate);

            $clickThrough = array(
                'group'    => 'mautic.email.stats',
                'label'    => 'mautic.email.abtest.criteria.clickthrough',
                'callback' => '\Mautic\EmailBundle\Helper\AbTestHelper::determineClickthroughRateWinner'
            );
            $event->addAbTestWinnerCriteria('email.clickthrough', $clickThrough);
        }

        $tokens = array(
            '{unsubscribe_text}' => $this->translator->trans('mautic.email.token.unsubscribe_text'),
            '{webview_text}'     => $this->translator->trans('mautic.email.token.webview_text')
        );

        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens),
                true
            );
        }

        // these should not allow visual tokens
        $tokens = array(
            '{unsubscribe_url}'  => $this->translator->trans('mautic.email.token.unsubscribe_url'),
            '{webview_url}'      => $this->translator->trans('mautic.email.token.webview_url')
        );
        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
            );
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $idHash  = $event->getIdHash();
        if ($idHash == null) {
            // Generate a bogus idHash to prevent errors for routes that may include it
            $idHash = uniqid();
        }
        $model = $this->factory->getModel('email');

        $unsubscribeText = $this->factory->getParameter('unsubscribe_text');
        if (!$unsubscribeText) {
            $unsubscribeText = $this->translator->trans('mautic.email.unsubscribe.text', array('%link%' => '|URL|'));
        }
        $unsubscribeText = str_replace('|URL|', $model->buildUrl('mautic_email_unsubscribe', array('idHash' => $idHash)), $unsubscribeText);
        $event->addToken('{unsubscribe_text}', $unsubscribeText);

        $event->addToken('{unsubscribe_url}', $model->buildUrl('mautic_email_unsubscribe', array('idHash' => $idHash)));

        $webviewText = $this->factory->getParameter('webview_text');
        if (!$webviewText) {
            $webviewText = $this->translator->trans('mautic.email.webview.text', array('%link%' => '|URL|'));
        }
        $webviewText = str_replace('|URL|', $model->buildUrl('mautic_email_webview', array('idHash' => $idHash)), $webviewText);
        $event->addToken('{webview_text}', $webviewText);

        $event->addToken('{webview_url}', $model->buildUrl('mautic_email_webview', array('idHash' => $idHash)));

    }
}