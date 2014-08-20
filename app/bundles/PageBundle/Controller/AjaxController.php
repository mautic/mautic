<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\PageBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function pageListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('page.page')->getLookupResults('page', $filter);
        $dataArray = array();

        foreach ($results as $r) {
            $dataArray[] = array(
                "label" => $r['title'] . " ({$r['id']}:{$r['alias']})",
                "value" => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function setBuilderContentAction(Request $request)
    {
        $newContent = InputHelper::html($request->request->get('content'));
        $page       = InputHelper::clean($request->request->get('page'));
        $slot       = InputHelper::clean($request->request->get('slot'));
        $dataArray  = array('success' => 0);
        if (!empty($page) && !empty($slot)) {
            $session = $this->factory->getSession();
            $content = $session->get('mautic.pagebuilder.'.$page.'.content', array());
            $content[$slot] = $newContent;
            $session->set('mautic.pagebuilder.'.$page.'.content', $content);
            $dataArray['success'] = 1;
        }
        return $this->sendJsonResponse($dataArray);
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function categoryListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('page.page')->getLookupResults('category', $filter, 10);
        $dataArray = array();
        foreach ($results as $r) {
            $dataArray[] = array(
                "label" => $r['title'] . " ({$r['id']})",
                "value" => $r['id']
            );
        }
        return $this->sendJsonResponse($dataArray);
    }
}