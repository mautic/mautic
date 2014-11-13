<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Helper\EmailTokenHelper;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    public function onEmailBuild (EmailBuilderEvent $event)
    {
        //add email tokens
        $tokenHelper = new EmailTokenHelper($this->factory);
        $event->addTokenSection('page.emailtokens', 'mautic.page.email.header.index', $tokenHelper->getTokenContent());
    }

    public function onEmailGenerate (EmailSendEvent $event)
    {
        static $pages = array(), $links = array();

        $content           = $event->getContent();
        $pagelinkRegex     = '/{pagelink=(.*?)}/';
        $externalLinkRegex = '/{externallink=(.*?)}/';

        $pageModel     = $this->factory->getModel('page');
        $redirectModel = $this->factory->getModel('page.redirect');
        $source        = $event->getSource();
        $clickthrough  = array('source' => $source);
        $lead          = $event->getLead();
        if ($lead !== null) {
            $clickthrough['lead'] = $lead['id'];
        }

        foreach ($content as $slot => &$html) {
            preg_match_all($pagelinkRegex, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (empty($pages[$match])) {
                        $pages[$match] = $pageModel->getEntity($match);
                    }

                    $url  = ($pages[$match] !== null) ? $pageModel->generateUrl($pages[$match], true, $clickthrough) : '';
                    $html = str_ireplace('{pagelink=' . $match . '}', $url, $html);
                }
            }

            preg_match_all($externalLinkRegex, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (empty($links[$match])) {
                        $links[$match] = $redirectModel->getRedirect($match, true);
                    }

                    $url  = ($links[$match] !== null) ? $redirectModel->generateRedirectUrl($links[$match], $clickthrough) : '';
                    $html = str_ireplace('{externallink=' . $match . '}', $url, $html);
                }
            }
        }

        $event->setContent($content);
    }
}