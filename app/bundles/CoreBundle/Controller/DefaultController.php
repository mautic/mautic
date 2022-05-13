<?php

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController.
 *
 * Almost all other Mautic Bundle controllers extend this default controller
 */
class DefaultController extends CommonController
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $root = $this->coreParametersHelper->get('webroot');

        if (empty($root)) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        } else {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel = $this->getModel('page');
            $page      = $pageModel->getEntity($root);

            if (empty($page)) {
                return $this->notFound();
            }

            $slug = $pageModel->generateSlug($page);

            $request->attributes->set('ignore_mismatch', true);

            return $this->forward('MauticPageBundle:Public:index', ['slug' => $slug]);
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function globalSearchAction()
    {
        $searchStr = $this->request->get('global_search', $this->get('session')->get('mautic.global_search', ''));
        $this->get('session')->set('mautic.global_search', $searchStr);

        if (!empty($searchStr)) {
            $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
            $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);
            $results = $event->getResults();
        } else {
            $results = [];
        }

        return $this->render('MauticCoreBundle:GlobalSearch:globalsearch.html.twig',
            [
                'results'      => $results,
                'searchString' => $searchStr,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function notificationsAction()
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->getModel('core.notification');

        list($notifications, $showNewIndicator, $updateMessage) = $model->getNotificationContent(null, false, 200);

        return $this->delegateView(
            [
                'contentTemplate' => 'MauticCoreBundle:Notification:notifications.html.twig',
                'viewParameters'  => [
                    'showNewIndicator' => $showNewIndicator,
                    'notifications'    => $notifications,
                    'updateMessage'    => $updateMessage,
                ],
            ]
        );
    }
}
