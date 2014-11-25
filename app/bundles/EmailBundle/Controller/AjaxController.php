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
        $newContent = InputHelper::html($request->request->get('content'));
        $email      = InputHelper::clean($request->request->get('email'));
        $slot       = InputHelper::clean($request->request->get('slot'));
        $dataArray  = array('success' => 0);
        if (!empty($email) && !empty($slot)) {
            $session = $this->factory->getSession();
            $content = $session->get('mautic.emailbuilder.'.$email.'.content', array());
            $content[$slot] = $newContent;
            $session->set('mautic.emailbuilder.'.$email.'.content', $content);
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
                    $form     = $this->get('form.factory')->create('email_abtest_settings', array(), array('formType' => $formType));
                    $html     = $this->renderView('MauticEmailBundle:AbTest:form.html.php', array(
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