<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\DTO\TokenFormatOptions;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PageSubscriber implements EventSubscriberInterface
{
    private string $regex = '{focus=(.*?)}';

    public function __construct(
        private readonly FocusModel $model,
        private readonly TokenHelper $tokenHelper,
        private readonly BuilderTokenHelperFactory $builderTokenHelperFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['onPageDisplay', 0],
            PageEvents::PAGE_ON_BUILD   => ['onPageBuild', 0],
        ];
    }

    /**
     * Add forms to available page tokens.
     */
    public function onPageBuild(PageBuilderEvent $event): void
    {
        if ($event->tokensRequested($this->regex)) {
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('focus', $this->model->getPermissionBase(), 'MauticFocusBundle', 'mautic.focus');
            $tokenFilter = $event->getTokenFilter();
            $tokens      = $tokenHelper->getFormattedTokens(
                $this->regex,
                TokenFormatOptions::simplePrefix('mautic.focus.focus_item'),
                'label' === $tokenFilter['target'] ? $tokenFilter['filter'] : '',
            );
            if ([] !== $tokens) {
                $event->addTokens($tokens);
            }
        }
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        $event->setContent(strtr($event->getContent(), $this->tokenHelper->findFocusTokens($event->getContent())));
    }
}
