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
        $results   = $this->getModel('page.page')->getLookupResults('page', $filter);
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
        $dataArray = array('success' => 0);
        $entityId  = InputHelper::clean($request->request->get('entity'));
        $session   = $this->factory->getSession();

        if (!empty($entityId)) {
            $sessionVar = 'mautic.pagebuilder.' . $entityId . '.content';

            // Check for an array of slots
            $slots   = InputHelper::_($request->request->get('slots', array(), true), 'html');
            $content = $session->get($sessionVar, array());

            if (!is_array($content)) {
                $content = array();
            }

            if (!empty($slots)) {
                // Builder was closed so save each content
                foreach ($slots as $slot => $newContent) {
                    $content[$slot] = $newContent;
                }

                $session->set($sessionVar, $content);
                $dataArray['success'] = 1;
            } else {
                // Check for a single slot
                $newContent = InputHelper::html($request->request->get('content'));
                $slot       = InputHelper::clean($request->request->get('slot'));

                if (!empty($slot)) {
                    $content[$slot] = $newContent;
                    $session->set($sessionVar, $content);
                    $dataArray['success'] = 1;
                }
            }
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
            $model   = $this->getModel('page');

            $page = $model->getEntity($pageId);

            $abTestComponents = $model->getBuilderComponents($page, 'abTestWinnerCriteria');
            $abTestSettings   = $abTestComponents['criteria'];

            if (isset($abTestSettings[$type])) {
                $html     = '';
                $formType = (!empty($abTestSettings[$type]['formType'])) ? $abTestSettings[$type]['formType'] : '';
                if (!empty($formType)) {
                    $formOptions = (!empty($abTestSettings[$type]['formTypeOptions'])) ? $abTestSettings[$type]['formTypeOptions'] : array();
                    $form        = $this->get('form.factory')->create('page_abtest_settings', array(), array('formType' => $formType, 'formTypeOptions' => $formOptions));
                    $html        = $this->renderView('MauticPageBundle:AbTest:form.html.php', array(
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

    /**
     * Called by parent::getBuilderTokensAction()
     *
     * @param $query
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model  = $this->getModel('page');

        return $model->getBuilderComponents(null, array('tokens', 'visualTokens'), $query);
    }

}
