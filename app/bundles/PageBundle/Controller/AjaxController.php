<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\VariantAjaxControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    use VariantAjaxControllerTrait;

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAbTestFormAction(Request $request)
    {
        return $this->getAbTestForm(
            $request,
            'page',
            'page_abtest_settings',
            'page',
            'MauticPageBundle:AbTest:form.html.php',
            ['MauticPageBundle:AbTest:form.html.php', 'MauticPageBundle:FormTheme\Page']
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function pageListAction(Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->getModel('page.page')->getLookupResults('page', $filter);
        $dataArray = [];

        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']}:{$r['alias']})",
                'value' => $r['id'],
            ];
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
        $dataArray = ['success' => 0];
        $entityId  = InputHelper::clean($request->request->get('entity'));
        $session   = $this->get('session');

        if (!empty($entityId)) {
            $sessionVar = 'mautic.pagebuilder.'.$entityId.'.content';

            // Check for an array of slots
            $slots   = InputHelper::_($request->request->get('slots', [], true), 'html');
            $content = $session->get($sessionVar, []);

            if (!is_array($content)) {
                $content = [];
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
     * Called by parent::getBuilderTokensAction().
     *
     * @param $query
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page');

        return $model->getBuilderComponents(null, ['tokens'], $query);
    }
}
