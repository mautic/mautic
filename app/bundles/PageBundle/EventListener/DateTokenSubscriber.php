<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTokenSubscriber implements EventSubscriberInterface
{
    private DateTokenHelper $dateTokenHelper;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, DateTokenHelper $dateTokenHelper)
    {
        $this->translator      = $translator;
        $this->dateTokenHelper = $dateTokenHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_BUILD                     => ['onPageBuild', 0],
            PageEvents::PAGE_ON_DISPLAY                   => ['onPageDisplay', 0],
        ];
    }

    public function onPageBuild(PageBuilderEvent $event)
    {
        $event->addToken('{today}', $this->translator->trans('mautic.email.token.today'));
    }

    public function onPageDisplay(PageDisplayEvent $event)
    {
        $content   = $event->getContent();
        $tokenList = $this->dateTokenHelper->getTokens($content);
        $event->setContent(str_replace(array_keys($tokenList), $tokenList, $content));
    }
}
