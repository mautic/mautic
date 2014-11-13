<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\AssetBundle\Helper\BuilderTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class BuilderSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class BuilderSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0),
            PageEvents::PAGE_ON_BUILD     => array('onPageBuild', 0),
            PageEvents::PAGE_ON_DISPLAY   => array('onPageDisplay', 0)
        );
    }

    public function onEmailBuild (EmailBuilderEvent $event)
    {
        $this->addTokens($event);
    }

    public function onPageBuild (PageBuilderEvent $event)
    {
        $this->addTokens($event);
    }

    private function addTokens ($event)
    {
        //add email tokens
        $tokenHelper = new BuilderTokenHelper($this->factory);
        $event->addTokenSection('asset.emailtokens', 'mautic.asset.builder.header.index', $tokenHelper->getTokenContent());
    }

    public function onEmailGenerate (EmailSendEvent $event)
    {
        $lead   = $event->getLead();
        $leadId = ($lead !== null) ? $lead['id'] : null;
        $this->replaceTokens($event, $leadId, $event->getSource());
    }

    public function onPageDisplay (PageEvent $event)
    {
        $page   = $event->getPage();
        $leadId = ($this->factory->getSecurity()->isAnonymous()) ? $this->factory->getModel('lead')->getCurrentLead()->getId() : null;
        $this->replaceTokens($event, $leadId, array('page', $page->getId()));
    }

    private function replaceTokens ($event, $leadId, $source = array())
    {
        static $assets = array();

        $content       = $event->getContent();
        $pagelinkRegex = '/{assetlink=(.*?)}/';
        $model         = $this->factory->getModel('asset');
        $clickthrough  = array('source' => $source);

        if ($leadId !== null) {
            $clickthrough['lead'] = $leadId;
        }

        foreach ($content as $slot => &$html) {
            preg_match_all($pagelinkRegex, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (empty($assets[$match])) {
                        $assets[$match] = $model->getEntity($match);
                    }

                    $url  = ($assets[$match] !== null) ? $model->generateUrl($assets[$match], true, $clickthrough) : '';
                    $html = str_ireplace('{assetlink=' . $match . '}', $url, $html);
                }
            }
        }

        $event->setContent($content);
    }
}