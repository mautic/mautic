<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
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
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAbTestFormAction(Request $request)
    {
        $dataArray = array(
            'success' => 0,
            'html'    => ''
        );
        $type   = InputHelper::clean($request->request->get('abKey'));
        $pageId = InputHelper::int($request->request->get('pageId'));

        if (!empty($type)) {
            //get the HTML for the form
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model   = $this->factory->getModel('page');

            $page = $model->getEntity($pageId);

            $abTestComponents = $model->getBuilderComponents($page, 'abTestWinnerCriteria');
            $abTestSettings   = $abTestComponents['criteria'];

            if (isset($abTestSettings[$type])) {
                $html     = '';
                $formType = (!empty($abTestSettings[$type]['formType'])) ? $abTestSettings[$type]['formType'] : '';
                if (!empty($formType)) {
                    $form     = $this->get('form.factory')->create('page_abtest_settings', array(), array('formType' => $formType));
                    $html     = $this->renderView('MauticPageBundle:AbTest:form.html.php', array(
                        'form' => $this->setFormTheme($form, 'MauticPageBundle:AbTest:form.html.php', 'MauticPageBundle:FormTheme\Page')
                    ));
                }

                $html = str_replace(array(
                    'page_abtest_settings[',
                    'page_abtest_settings_',
                    'page_abtest_settings'
                ), array(
                    'page[variantSettings][',
                    'page_variantSettings_',
                    'page'
                ), $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}
