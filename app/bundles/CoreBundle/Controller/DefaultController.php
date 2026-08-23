<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Almost all other Mautic Bundle controllers extend this default controller.
 */
final class DefaultController extends CommonController
{
    private NotificationModel $notificationModel;

    private PageModel $pageModel;

    #[Required]
    public function autowireDefaultController(
        NotificationModel $notificationModel,
        PageModel $pageModel,
    ): void {
        $this->notificationModel = $notificationModel;
        $this->pageModel = $pageModel;
    }

    #[Route(
        '/',
        name: 'mautic_base_index',
        priority: -209
    )]
    public function indexAction(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|Response
    {
        $root = $this->coreParametersHelper->get('webroot');

        if (empty($root)) {
            return $this->redirectToRoute('mautic_dashboard_index');
        }
        $page      = $this->pageModel->getEntity($root);

        if (!$page instanceof \Mautic\PageBundle\Entity\Page) {
            return $this->notFound();
        }

        $slug = $this->pageModel->generateSlug($page);

        $request->attributes->set('ignore_mismatch', true);

        return $this->forward('Mautic\PageBundle\Controller\PublicController::indexAction', ['slug' => $slug]);
    }

    public function globalSearchAction(Request $request): Response
    {
        $searchStr = $request->get('global_search', $request->getSession()->get('mautic.global_search', ''));
        $request->getSession()->set('mautic.global_search', $searchStr);

        if (!empty($searchStr)) {
            $event = new GlobalSearchEvent($searchStr, $this->translator);
            $this->dispatcher->dispatch($event);
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

    public function notificationsAction(): Response
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
