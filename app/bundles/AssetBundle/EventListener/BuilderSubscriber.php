<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class BuilderSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class BuilderSubscriber extends CommonSubscriber
{

    private $assetToken = '{assetlink=(.*?)}';

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_BUILD   => array('onBuilderBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0),
            PageEvents::PAGE_ON_BUILD     => array('onBuilderBuild', 0),
            PageEvents::PAGE_ON_DISPLAY   => array('onPageDisplay', 0)
        );
    }

    /**
     * @param BuilderEvent $event
     */
    public function onBuilderBuild(BuilderEvent $event)
    {
        if ($event->tokenSectionsRequested()) {
            $this->addTokenSections($event);
        }

        if ($event->tokensRequested($this->assetToken)) {
            $tokenHelper = new BuilderTokenHelper($this->factory, 'asset');

            $event->addTokensFromHelper($tokenHelper, $this->assetToken, 'title', 'id', false, true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $lead   = $event->getLead();
        $leadId = ($lead !== null) ? $lead['id'] : null;
        $email  = $event->getEmail();
        $tokens = $this->generateTokensFromContent($event, $leadId, $event->getSource(), ($email === null) ? null : $email->getId());
        $event->addTokens($tokens);
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $page   = $event->getPage();
        $leadId = ($this->factory->getSecurity()->isAnonymous()) ? $this->factory->getModel('lead')->getCurrentLead()->getId() : null;
        $tokens = $this->generateTokensFromContent($event, $leadId, array('page', $page->getId()));

        $content = $event->getContent();
        if (!empty($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }
        $event->setContent($content);
    }

    /**
     * @param $event
     */
    private function addTokenSections($event)
    {
        //add email tokens
        $tokenHelper = new BuilderTokenHelper($this->factory, 'asset');
        $event->addTokenSection('asset.emailtokens', 'mautic.asset.assets', $tokenHelper->getTokenContent(), -255);
    }

    /**
     * @param       $event
     * @param       $leadId
     * @param array $source
     * @param null  $emailId
     *
     * @return array
     */
    private function generateTokensFromContent($event, $leadId, $source = array(), $emailId = null)
    {
        $content       = $event->getContent();
        $pagelinkRegex = '/'.$this->assetToken.'/';

        /** @var \Mautic\AssetBundle\Model\AssetModel $model */
        $model = $this->factory->getModel('asset');

        $clickthrough = array();
        if ($event instanceof PageDisplayEvent || ($event instanceof EmailSendEvent && !$event->isInternalSend())) {
            $clickthrough = array('source' => $source);

            if ($leadId !== null) {
                $clickthrough['lead'] = $leadId;
            }

            if (!empty($emailId)) {
                $clickthrough['email'] = $emailId;
            }
        }

        $tokens = array();

        preg_match_all($pagelinkRegex, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $assetId) {
                $token = $matches[0][$key];

                if (isset($tokens[$token])) {
                    continue;
                }

                $asset          = $model->getEntity($assetId);
                $tokens[$token] = ($asset !== null) ? $model->generateUrl($asset, true, $clickthrough) : '';
            }
        }

        return $tokens;
    }
}
