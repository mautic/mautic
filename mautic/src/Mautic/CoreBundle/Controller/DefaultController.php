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
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function indexAction()
    {
        if ($this->request->isXmlHttpRequest() && !$this->request->get("ignoreAjax", false)) {
            return $this->ajaxAction();
        } else {
            return $this->render('MauticCoreBundle:Default:index.html.php');
        }
    }

    public function globalSearchAction()
    {
        $searchStr = $this->request->request->get("searchstring", $this->get('session')->get('mautic.global_search', ''));
        $this->get('session')->set('mautic.global_search', $searchStr);

        $event     = new GlobalSearchEvent($searchStr);
        $this->get('event_dispatcher')->dispatch(CoreEvents::GLOBAL_SEARCH, $event);

        return $this->render('MauticCoreBundle:Default:globalsearchresults.html.php',
            array('results'      => $event->getResults())
        );

    }
}