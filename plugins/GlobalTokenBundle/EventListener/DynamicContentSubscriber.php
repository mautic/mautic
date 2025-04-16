<?php

declare(strict_types=1);

namespace MauticPlugin\GlobalTokenBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use MauticPlugin\GlobalTokenBundle\Helper\TokenHelperInterface;
use MauticPlugin\GlobalTokenBundle\Provider\ConfigProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DynamicContentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigProviderInterface $configProvider,
        private TokenHelperInterface $tokenHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 100],
        ];
    }

    public function onTokenReplacement(TokenReplacementEvent $event): void
    {
        if (!$this->configProvider->pluginIsEnabled() || empty($event->getContent())) {
            return;
        }
        $event->setContent($this->tokenHelper->replaceTokens($event->getContent()));
    }
}
