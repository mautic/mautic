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
    public function indexAction()
    {
        return $this->delegateView('MauticDashboardBundle:Default:index.html.php');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function globalSearchAction()
    {
        $searchStr = $this->request->request->get("searchstring", $this->factory->getSession()->get('mautic.global_search', ''));
        $this->factory->getSession()->set('mautic.global_search', $searchStr);

        if (!empty($searchStr)) {
            $event = new GlobalSearchEvent($searchStr, $this->get('translator'));
            $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);
            $results = $event->getResults();
        } else {
            $results = array();
        }

        return $this->render('MauticCoreBundle:Default:globalsearchresults.html.php',
            array('results' => $results)
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function notificationsAction()
    {
        /** @var \Mautic\CoreBundle\Model\NotificationModel $model */
        $model         = $this->factory->getModel('core.notification');
        $notifications = $model->getNotifications();

        $showNewIndicator = false;

        //determine if the new message indicator should be shown
        foreach ($notifications as $n) {
            if (!$n['isRead']) {
                $showNewIndicator = true;
                break;
            }
        }

        // Check for updates
        $updateMessage = '';
        if ($this->factory->getUser()->isAdmin()) {
            $session = $this->factory->getSession();

            //check to see when we last checked for an update
            $lastChecked = $session->get('mautic.update.checked', 0);

            if (time() - $lastChecked > 3600) {
                $session->set('mautic.update.checked', time());

                /** @var \Mautic\CoreBundle\Helper\UpdateHelper $updateHelper */
                $updateHelper = $this->factory->getHelper('update');
                $updateData   = $updateHelper->fetchData();

                // If the version key is set, we have an update
                if (isset($updateData['version'])) {
                    $translator    = $this->factory->getTranslator();
                    $updateMessage = $translator->trans($updateData['message'], array('%version%' => $updateData['version'], '%announcement%' => $updateData['announcement']));
                }
            }
        }

        return $this->delegateView(array(
            'contentTemplate' => 'MauticCoreBundle:Notification:notifications.html.php',
            'viewParameters'  => array(
                'showNewIndicator' => $showNewIndicator,
                'notifications'    => $notifications,
                'updateMessage'    => $updateMessage
            )
        ));
    }
}
