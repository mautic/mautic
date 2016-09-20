<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     * @param string  $name
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderFieldsAction(Request $request, $name = 'fields')
    {
        $dataArray   = array('success' => 0);
        $sessionId   = InputHelper::clean($request->request->get('formId'));
        $sessionName = 'mautic.form.'.$sessionId.'.' . $name . '.modified';
        $session     = $this->get('session');
        $orderName   = ($name == 'fields') ? 'mauticform' : 'mauticform_action';
        $order       = InputHelper::clean($request->request->get($orderName));
        $components  = $session->get($sessionName);

        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set($sessionName, $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderActionsAction(Request $request) {
        return $this->reorderFieldsAction($request, 'actions');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateFormFieldsAction(Request $request)
    {
        $formId    = InputHelper::int($request->request->get('formId'));
        $dataArray = array('success' => 0);
        $model = $this->getModel('form');
        $entity = $model->getEntity($formId);
        $formFields = $entity->getFields();
        $fields = array();

        foreach ($formFields as $field) {
            if ($field->getType() != 'button') {
                $properties = $field->getProperties();
                $options = array();

                if (!empty($properties['list']['list'])) {
                    $options = $properties['list']['list'];
                }

                $fields[] = array(
                    'id'      => $field->getId(),
                    'label'   => $field->getLabel(),
                    'alias'   => $field->getAlias(),
                    'type'    => $field->getType(),
                    'options' => $options
                );
            }
        }

        $dataArray['fields'] = $fields;
        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Ajax submit for forms
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function submitAction()
    {
        $response = $this->forwardWithPost('MauticFormBundle:Public:submit', $this->request->request->all(), [], ['ajax' => true]);
        $responseData = json_decode($response->getContent(), true);
        $success = (!in_array($response->getStatusCode(), [404, 500]) && empty($responseData['errorMessage'])
            && empty($responseData['validationErrors']));

        $message = '';
        $type    = '';
        if (isset($responseData['successMessage'])) {
            $message = $responseData['successMessage'];
            $type    = 'notice';
        } elseif (isset($responseData['errorMessage'])) {
            $message = $responseData['errorMessage'];
            $type    = 'error';
        }

        $data = array_merge($responseData, ['message' => $message, 'type' => $type, 'success' => $success]);

        return $this->sendJsonResponse($data);
    }
}
