<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\ButtonHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ButtonSubscriber implements EventSubscriberInterface
{
    public function __construct(private IntegrationsHelper $integrationsHelper, private CorePermissions $security, private RouterInterface $router, private TranslatorInterface $translator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0]];
    }

    public function injectViewButtons(CustomButtonEvent $event): void
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return;
        }
        try {
            if (!$this->integrationsHelper->getIntegration('CompanyEnrich')->getIntegrationConfiguration()->getIsPublished()) {
                return;
            }
        } catch (IntegrationNotFoundException) {
            return;
        }
        $item = $event->getItem();
        if (!$item instanceof Lead && !$item instanceof Company) {
            return;
        }
        $kind = $item instanceof Lead ? 'contact' : 'company';
        if (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $item->getPermissionUser())
            || !$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $item->getPermissionUser())) {
            return;
        }
        $button = [
            'attr' => [
                'data-toggle' => 'ajaxmodal',
                'data-target' => '#MauticSharedModal',
                'data-header' => 'CompanyEnrich',
                'href' => $this->router->generate('mautic_companyenrich_enrich', ['kind' => $kind, 'id' => $item->getId()]),
            ],
            'btnText' => $this->translator->trans('mautic.companyenrich.fill'),
            'iconClass' => 'ri-search-line',
        ];
        if ($event->checkRouteContext(['mautic_'.$kind.'_action', ['objectAction' => 'view']])) {
            $event->addButton($button, ButtonHelper::LOCATION_PAGE_ACTIONS);
        }
        $event->addButton($button, ButtonHelper::LOCATION_LIST_ACTIONS, 'mautic_'.$kind.'_index');
    }
}
