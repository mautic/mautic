<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomButtonEvent;
use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ButtonSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(TranslatorInterface $translator, RouterInterface $router)
    {
        $this->translator = $translator;
        $this->router     = $router;
    }

    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_BUTTONS => ['injectViewButtons', 0],
        ];
    }

    public function injectViewButtons(CustomButtonEvent $event): void
    {
        if (false === strpos($event->getRoute(), 'mautic_contact_index')) {
            return;
        }

        $exportRoute = $this->router->generate('mautic_contact_action', ['objectAction' => 'contactExportScheduler']);

        $event->addButton(
            [
                'attr'      => [
                    'href'        => $exportRoute.'?filetype=csv',
                    'data-toggle' => null,
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.csv'),
                'iconClass' => 'fa fa-file-text-o',
            ],
            ButtonHelper::LOCATION_BULK_ACTIONS
        );

        $event->addButton(
            [
                'attr'      => [
                    'href'        => $exportRoute.'?filetype=xlsx',
                    'data-toggle' => null,
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.xlsx'),
                'iconClass' => 'fa fa-file-text-o',
            ],
            ButtonHelper::LOCATION_BULK_ACTIONS
        );

        $event->addButton(
            [
                'attr'      => [
                    'href'        => $exportRoute.'?filetype=csv',
                    'data-toggle' => null,
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.csv'),
                'iconClass' => 'fa fa-file-text-o',
            ],
            ButtonHelper::LOCATION_PAGE_ACTIONS
        );

        $event->addButton(
            [
                'attr'      => [
                    'href'        => $exportRoute.'?filetype=xlsx',
                    'data-toggle' => null,
                ],
                'btnText'   => $this->translator->trans('mautic.core.export.xlsx'),
                'iconClass' => 'fa fa-file-text-o',
            ],
            ButtonHelper::LOCATION_PAGE_ACTIONS
        );
    }
}
