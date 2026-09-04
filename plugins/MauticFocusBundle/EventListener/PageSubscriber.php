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
use Symfony\Contracts\Translation\TranslatorInterface;

final class PageSubscriber implements EventSubscriberInterface
{
    private string $regex = '{focus=(.*?)}';

    public function __construct(
        private readonly FocusModel $model,
        private readonly TokenHelper $tokenHelper,
        private readonly BuilderTokenHelperFactory $builderTokenHelperFactory,
        private readonly TranslatorInterface $translator,
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
            $focusTokens = $tokenHelper->getFormattedTokens(
                $this->regex,
                TokenFormatOptions::simplePrefix('mautic.focus.focus_item'),
                'label' === $tokenFilter['target'] ? $tokenFilter['filter'] : '',
            );
            $tokens = [];
            foreach ($focusTokens as $token => $label) {
                $parsedToken = $this->tokenHelper->parseToken($token);
                if (null === $parsedToken) {
                    continue;
                }

                $tokens[$this->tokenHelper->formatToken($parsedToken['id'], TokenHelper::MODE_DISPLAY)] = $label.' '.$this->translator->trans('mautic.focus.token.display');
                $tokens[$this->tokenHelper->formatToken($parsedToken['id'], TokenHelper::MODE_TRACKING)] = $label.' '.$this->translator->trans('mautic.focus.token.tracking');
            }

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
