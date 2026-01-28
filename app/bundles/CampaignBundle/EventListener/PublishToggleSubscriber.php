<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PublishToggleSubscriber implements EventSubscriberInterface
{
    public function __construct(private CoreParametersHelper $coreParametersHelper, private TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0],
        ];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if ('@MauticCore/Helper/publishstatus_icon.html.twig' !== $event->getTemplate()) {
            return;
        }

        if (empty($event->getVars()['item']) || !$event->getVars()['item'] instanceof Campaign) {
            return;
        }

        $republishBehavior  = $event->getVars()['item']->getRepublishBehavior() ?? $this->coreParametersHelper->get('campaign_republish_behavior');
        $republishBehavior  = $this->translator->trans('mautic.campaignconfig.campaign_republish_behavior.'.$republishBehavior);
        $vars               = $event->getVars();
        $vars['onclick']    = 'Mautic.confirmationCampaignPublishStatus(mQuery(this));';
        $vars['attributes'] = [
            'data-toggle'           => 'confirmation',
            'data-confirm-callback' => 'confirmCallbackCampaignPublishStatus',
            'data-cancel-callback'  => 'dismissConfirmation',
        ];
        $vars['transKeys'] = [
            'data-message-publish'   => $this->translator->trans('mautic.campaign.form.confirmation.message.publish', ['%republishBehavior%' => $republishBehavior]),
            'data-message-unpublish' => $this->translator->trans('mautic.campaign.form.confirmation.message'),
            'data-confirm-text'      => 'mautic.campaign.form.confirmation.confirm_text',
            'data-cancel-text'       => 'mautic.campaign.form.confirmation.cancel_text',
        ];

        $event->setVars($vars);
    }
}
