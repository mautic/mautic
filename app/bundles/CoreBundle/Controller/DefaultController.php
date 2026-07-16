<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Almost all other Mautic Bundle controllers extend this default controller.
 */
class DefaultController extends CommonController
{
    private \Mautic\CoreBundle\Model\NotificationModel $notificationModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowire(\Mautic\CoreBundle\Model\NotificationModel $notificationModel): void
    {
        $this->notificationModel = $notificationModel;
    }

    public function indexAction(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $root = $this->coreParametersHelper->get('webroot');

        if (empty($root)) {
            return $this->redirectToRoute('mautic_dashboard_index');
        }
        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel = $this->getModel('page');
        $page      = $pageModel->getEntity($root);

        if (empty($page)) {
            return $this->notFound();
        }

        $slug = $pageModel->generateSlug($page);

        $request->attributes->set('ignore_mismatch', true);

        return $this->forward('Mautic\PageBundle\Controller\PublicController::indexAction', ['slug' => $slug]);
    }

    public function globalSearchAction(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $searchStr = $request->get('global_search', $request->getSession()->get('mautic.global_search', ''));
        $request->getSession()->set('mautic.global_search', $searchStr);

        if (!empty($searchStr)) {
            $event = new GlobalSearchEvent($searchStr, $this->translator);
            $this->dispatcher->dispatch($event, CoreEvents::GLOBAL_SEARCH);
            $results = $event->getResults();
        } else {
            $results = [];
        }

        return $this->render('@MauticCore/GlobalSearch/globalsearch.html.twig',
            [
                'results'      => $results,
                'searchString' => $searchStr,
            ]
        );
    }

    public function notificationsAction(): \Symfony\Component\HttpFoundation\Response
    {
        [$notifications, $showNewIndicator, $updateMessage] = $this->notificationModel->getNotificationContent(null, false, 200);

        return $this->delegateView(
            [
                'contentTemplate' => '@MauticCore/Notification/notifications.html.twig',
                'viewParameters'  => [
                    'showNewIndicator' => $showNewIndicator,
                    'notifications'    => $notifications,
                    'updateMessage'    => $updateMessage,
                ],
            ]
        );
    }
}
