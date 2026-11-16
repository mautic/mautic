<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EmailPreviewButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectContent', 0],
        ];
    }

    public function injectContent(CustomContentEvent $event): void
    {
        if (!$event->checkContext('@MauticEmail/Email/preview.html.twig', 'email.preview.buttons')) {
            return;
        }

        $vars = $event->getVars();
        $event->addTemplate('@MauticEmail/Email/preview_button_link.html.twig', [
            'url' => $this->urlGenerator->generate('mautic_email_preview_download', [
                'objectId' => $vars['objectId'],
                'objectType' => $vars['objectType'],
                'contactId' => $vars['contactId'],
                'companyId' => $vars['companyId'],
                'downloadType' => 'html',
            ]),
            'label' => $this->translator->trans('mautic.email.preview.download_html'),
        ]);
    }
}
