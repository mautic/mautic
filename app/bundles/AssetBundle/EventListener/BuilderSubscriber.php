<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper;
use Mautic\CoreBundle\Event\BuilderEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    private string $assetToken = '{assetlink=(.*?)}';

    public function __construct(
        private CorePermissions $security,
        private TokenHelper $tokenHelper,
        private ContactTracker $contactTracker,
        private BuilderTokenHelperFactory $builderTokenHelperFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onBuilderBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
            PageEvents::PAGE_ON_BUILD     => ['onBuilderBuild', 0],
            PageEvents::PAGE_ON_DISPLAY   => ['onPageDisplay', 0],
        ];
    }

    public function onBuilderBuild(BuilderEvent $event): void
    {
        if ($event->tokensRequested($this->assetToken)) {
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('asset');
            $event->addTokensFromHelper($tokenHelper, $this->assetToken, 'title', 'id', true);
        }
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $lead   = $event->getLead();
        $leadId = (int) (null !== $lead ? $lead['id'] : null);
        $email  = $event->getEmail();
        $tokens = $this->generateTokensFromContent($event, $leadId, $event->getSource(), null === $email ? null : $email->getId());
        $event->addTokens($tokens);
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        if (!$lead = $event->getLead()) {
            $lead = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
        }

        $leadId  = $lead ? $lead->getId() : null;
        $page    = $event->getPage();
        $tokens  = $this->generateTokensFromContent($event, $leadId, ['page', $page->getId()]);
        $content = $event->getContent();

        if ([] !== $tokens) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }
        $event->setContent($content);
    }

    /**
     * @param PageDisplayEvent|EmailSendEvent $event
     * @param array                           $source
     * @param int|null                        $emailId
     *
     * @return mixed[]
     */
    private function generateTokensFromContent($event, ?int $leadId, $source = [], $emailId = null): array
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
