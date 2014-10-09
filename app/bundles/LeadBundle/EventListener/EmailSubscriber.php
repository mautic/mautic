<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class EmailSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class EmailSubscriber extends CommonSubscriber
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
        $fields = $this->factory->getModel('lead.field')->getEntities(array(
            'filter' => array('isPublished' => true),
            'hydration_mode' => 'HYDRATE_ARRAY'
        ));
        $content = $this->templating->render('MauticLeadBundle:EmailToken:token.html.php', array(
            'fields' => $fields
        ));
        $event->addTokenSection('lead.leadtokens', 'mautic.lead.email.header.index', $content);
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content  = $event->getContent();
        $regex    = '/{leadfield=(.*?)}/';

        $lead = $event->getLead();
        foreach ($content as $slot => &$html) {
            preg_match_all($regex, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (isset($lead[$match])) {
                        $html = str_ireplace('{leadfield=' . $match . '}', $lead[$match], $html);
                    }
                }
            }
        }

        $event->setContent($content);
    }
}