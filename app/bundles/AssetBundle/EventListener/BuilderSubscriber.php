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
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Tracker\ContactTracker;
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
     * @var ContactTracker
     */
    protected $contactTracker;

    /**
     * @var BuilderTokenHelperFactory
     */
    protected $builderTokenHelperFactory;

    /**
     * BuilderSubscriber constructor.
     *
     * @param TokenHelper               $tokenHelper
     * @param ContactTracker            $contactTracker
     * @param BuilderTokenHelperFactory $builderTokenHelperFactory
     */
    public function __construct(TokenHelper $tokenHelper, ContactTracker $contactTracker, BuilderTokenHelperFactory $builderTokenHelperFactory)
    {
        $this->tokenHelper               = $tokenHelper;
        $this->contactTracker            = $contactTracker;
        $this->builderTokenHelperFactory = $builderTokenHelperFactory;
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
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('asset');
            $event->addTokensFromHelper($tokenHelper, $this->assetToken, 'title', 'id', true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $lead   = $event->getLead();
        $leadId = (int) ($lead !== null ? $lead['id'] : null);
        $email  = $event->getEmail();
        $tokens = $this->generateTokensFromContent($event, $leadId, $event->getSource(), $email === null ? null : $email->getId());
        $event->addTokens($tokens);
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $page    = $event->getPage();
        $lead    = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
        $leadId  = $lead ? $lead->getId() : null;
        $tokens  = $this->generateTokensFromContent($event, $leadId, ['page', $page->getId()]);
        $content = $event->getContent();

        if (!empty($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }
        $event->setContent($content);
    }

    /**
     * @param PageDisplayEvent|EmailSendEvent $event
     * @param int                             $leadId
     * @param array                           $source
     * @param null                            $emailId
     *
     * @return array
     */
    private function generateTokensFromContent($event, $leadId, $source = [], $emailId = null)
    {
        if ($event instanceof PageDisplayEvent || ($event instanceof EmailSendEvent && $event->shouldAppendClickthrough())) {
            $clickthrough = [
                'source' => $source,
                'lead'   => $leadId ?? false,
                'email'  => $emailId ?? false,
            ];
        }

        return $this->tokenHelper->findAssetTokens($event->getContent(), array_filter($clickthrough ?? []));
    }
}
