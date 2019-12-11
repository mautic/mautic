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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $assetToken = '{assetlink=(.*?)}';

    /**
     * @var TokenHelper
     */
    private $tokenHelper;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param TokenHelper     $tokenHelper
     * @param LeadModel       $leadModel
     * @param CorePermissions $security
     * @param MauticFactory   $factory
     */
    public function __construct(TokenHelper $tokenHelper, LeadModel $leadModel, CorePermissions $security, MauticFactory $factory)
    {
        $this->tokenHelper = $tokenHelper;
        $this->leadModel   = $leadModel;
        $this->security    = $security;
        $this->factory     = $factory; // Temporary. @see https://github.com/mautic/mautic/issues/8088
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
            $event->addTokensFromHelper($tokenHelper, $this->assetToken, 'title', 'id', true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $lead   = $event->getLead();
        $leadId = (null !== $lead) ? $lead['id'] : null;
        $email  = $event->getEmail();
        $tokens = $this->generateTokensFromContent($event, $leadId, $event->getSource(), (null === $email) ? null : $email->getId());
        $event->addTokens($tokens);
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function onPageDisplay(PageDisplayEvent $event)
    {
        $page    = $event->getPage();
        $lead    = ($this->security->isAnonymous()) ? $this->leadModel->getCurrentLead() : null;
        $leadId  = ($lead) ? $lead->getId() : null;
        $tokens  = $this->generateTokensFromContent($event, $leadId, ['page', $page->getId()]);
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

            if (null !== $leadId) {
                $clickthrough['lead'] = $leadId;
            }

            if (!empty($emailId)) {
                $clickthrough['email'] = $emailId;
            }
        }

        return $this->tokenHelper->findAssetTokens($content, $clickthrough);
    }
}
