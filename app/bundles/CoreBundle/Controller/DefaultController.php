<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\GlobalSearchEvent;

/**
 * Class DefaultController
 * Almost all other Mautic Bundle controllers extend this default controller
 *
 * @package Mautic\CoreBundle\Controller
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
        return $this->delegateView('MauticCoreBundle:Default:index.html.php');
    }

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
}