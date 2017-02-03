<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class BuilderSubscriber.
 */
class BuilderSubscriber extends CommonSubscriber
{
    /**
     * @var string
     */
    protected $assetToken = '{assetlink=(.*?)}';

    /**
     * @var TokenHelper
     */
    protected $tokenHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * BuilderSubscriber constructor.
     *
     * @param TokenHelper $tokenHelper
     * @param LeadModel   $leadModel
     */
    public function __construct(TokenHelper $tokenHelper, LeadModel $leadModel)
    {
        $this->tokenHelper = $tokenHelper;
        $this->leadModel   = $leadModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onBuilderBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
            PageEvents::PAGE_ON_BUILD     => ['onBuilderBuild', 0],
            PageEvents::PAGE_ON_DISPLAY   => ['onPageDisplay', 0],
        ];
    }

    /**
     * @param BuilderEvent $event
     */
    public function onBuilderBuild(BuilderEvent $event)
    {
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
        $leadId = ($this->security->isAnonymous()) ? $this->leadModel->getCurrentLead()->getId() : null;
        $tokens = $this->generateTokensFromContent($event, $leadId, ['page', $page->getId()]);

        $content = $event->getContent();
        if (!empty($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }
        $event->setContent($content);
    }

    /**
     * @param       $event
     * @param       $leadId
     * @param array $source
     * @param null  $emailId
     *
     * @return array
     */
    private function generateTokensFromContent($event, $leadId, $source = [], $emailId = null)
    {
        $content = $event->getContent();

        $clickthrough = [];
        if ($event instanceof PageDisplayEvent || ($event instanceof EmailSendEvent && $event->shouldAppendClickthrough())) {
            $clickthrough = ['source' => $source];

            if ($leadId !== null) {
                $clickthrough['lead'] = $leadId;
            }

            if (!empty($emailId)) {
                $clickthrough['email'] = $emailId;
            }
        }

        return $this->tokenHelper->findAssetTokens($content, $clickthrough);
    }
}
