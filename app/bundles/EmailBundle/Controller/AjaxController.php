<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 *
 * @package Mautic\EmailBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function setBuilderContentAction(Request $request)
    {
        $dataArray  = array('success' => 0);
        $entityId   = InputHelper::clean($request->request->get('entity'));
        $session    = $this->factory->getSession();

        if (!empty($entityId)) {
            $sessionVar = 'mautic.emailbuilder.' . $entityId . '.content';

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
        $emailId = InputHelper::int($request->request->get('emailId'));

        if (!empty($type)) {
            //get the HTML for the form
            /** @var \Mautic\EmailBundle\Model\EmailModel $model */
            $model   = $this->factory->getModel('email');

            $email = $model->getEntity($emailId);

            $abTestComponents = $model->getBuilderComponents($email, 'abTestWinnerCriteria');
            $abTestSettings   = $abTestComponents['criteria'];

            if (isset($abTestSettings[$type])) {
                $html     = '';
                $formType = (!empty($abTestSettings[$type]['formType'])) ? $abTestSettings[$type]['formType'] : '';
                if (!empty($formType)) {
                    $formOptions = (!empty($abTestSettings[$type]['formTypeOptions'])) ? $abTestSettings[$type]['formTypeOptions'] : array();
                    $form        = $this->get('form.factory')->create('email_abtest_settings', array(), array('formType' => $formType, 'formTypeOptions' => $formOptions));
                    $html        = $this->renderView('MauticEmailBundle:AbTest:form.html.php', array(
                        'form' => $this->setFormTheme($form, 'MauticEmailBundle:AbTest:form.html.php', 'MauticEmailBundle:FormTheme\Email')
                    ));
                }

                $html = str_replace(array(
                    'email_abtest_settings[',
                    'email_abtest_settings_',
                    'email_abtest_settings'
                ), array(
                    'emailform[variantSettings][',
                    'emailform_variantSettings_',
                    'emailform'
                ), $html);
                $dataArray['html']    = $html;
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }
}