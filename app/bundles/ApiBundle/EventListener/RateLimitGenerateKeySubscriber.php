<?php

namespace Mautic\ApiBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RateLimitGenerateKeySubscriber implements EventSubscriberInterface
{
    public function __construct(private CoreParametersHelper $coreParametersHelper)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
          RateLimitEvents::GENERATE_KEY => ['onGenerateKey', 0],
        ];
    }

    public function onGenerateKey(GenerateKeyEvent $event): void
    {
        $suffix = rawurlencode($this->coreParametersHelper->get('site_url'));
        $event->addToKey($suffix);
    }
}
