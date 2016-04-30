<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 *
 * Almost all other Mautic Bundle controllers extend this default controller
 */
class DefaultController extends CommonController
{
    /**
     * Generates default index.php
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $root = $this->factory->getParameter('webroot');

        if (empty($root)) {
            return $this->redirect($this->generateUrl('mautic_dashboard_index'));
        } else {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel = $this->factory->getModel('page');
            $page      = $pageModel->getEntity($root);

            if (empty($page)) {

                $this->notFound();
            }

            $slug = $pageModel->generateSlug($page);

            $request->attributes->set('ignore_mismatch', true);

            return $this->forward('MauticPageBundle:Public:index', array('slug' => $slug));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function globalSearchAction()
    {
        $searchStr = $this->request->get("global_search", $this->factory->getSession()->get('mautic.global_search', ''));
        $this->factory->getSession()->set('mautic.global_search', $searchStr);

        if (!empty($searchStr)) {
            $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
            $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);
            $results = $event->getResults();
        } else {
            $results = array();
        }

        return $this->render('MauticCoreBundle:GlobalSearch:globalsearch.html.php',
            array(
                'results'      => $results,
                'searchString' => $searchStr
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function notificationsAction()
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model = $this->factory->getModel('core.notification');

        list($notifications, $showNewIndicator, $updateMessage) = $model->getNotificationContent();

        return $this->delegateView(array(
            'contentTemplate' => 'MauticCoreBundle:Notification:notifications.html.php',
            'viewParameters'  => array(
                'showNewIndicator' => $showNewIndicator,
                'notifications'    => $notifications,
                'updateMessage'    => $updateMessage
            )
        ));
    }

    /**
     * @param Request $request
     *
     * @deprecated Temp fix for pre 1.0.0-rc1
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function publicBcRedirectAction(Request $request)
    {
        $requestUri = $request->getRequestUri();

        $url = str_replace('/p/', '/', $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * @param Request $request
     *
     * @deprecated Temp fix for pre 1.0.0-rc2
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function ajaxBcRedirectAction(Request $request)
    {
        $requestUri = $request->getRequestUri();

        if ($actionQuery = $request->query->get('action', false)) {
            if (strpos($actionQuery, 'core:updateDatabaseMigration') !== false) {
                // Check for update request and forward to controller if requesting an update so the process will finish
                $actionQuery = str_replace('core:', '', $actionQuery);
                return $this->forward("MauticCoreBundle:Ajax:executeAjax", array(
                    'action'  => $actionQuery,
                    //forward the request as well as Symfony creates a subrequest without GET/POST
                    'request' => $this->request
                ));
            }
        }

        $url = str_replace('/ajax', '/s/ajax', $requestUri);

        return $this->redirect($url, 301);
    }

    /**
     * @param Request $request
     *
     * @deprecated Temp fix for pre 1.0.0-rc2
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateBcRedirectAction(Request $request)
    {
        $requestUri = $request->getRequestUri();

        $url = str_replace('/update', '/s/update', $requestUri);

        return $this->redirect($url, 301);
    }
}
