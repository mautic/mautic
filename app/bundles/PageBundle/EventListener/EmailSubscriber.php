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
        $content      = $event->getContent();
        $regex        = '/{pagelink=(.*?)}/';
        $model        = $this->factory->getModel('page');
        $pages        = array();

        $source = $event->getSource();

        foreach ($content as $slot => &$html) {
            preg_match_all($regex, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (empty($pages[$match])) {
                        $page          = $model->getEntity($match);

                        $pages[$match] = ($page !== null) ? $model->generateUrl($page, true, array('source' => $source)) : '';
                    }
                    $html = str_ireplace('{pagelink=' . $match . '}', $pages[$match], $html);
                }
            }
        }

        $event->setContent($content);
    }
}