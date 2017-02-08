<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractModalFormController extends AbstractStandardFormController
{
    /**
     * @param $action
     * @param $objectId
     *
     * @return mixed
     */
    abstract protected function getFormData($action, $objectId);

    /**
     * @param      $data
     * @param      $action
     * @param null $objectId
     *
     * @return Form
     */
    abstract protected function getActionForm($data, $action, $objectId = null);

    /**
     * @param      $data
     * @param Form $form
     * @param      $action
     * @param null $objectId
     *
     * @return mixed
     */
    abstract protected function processFormData($data, Form $form, $action, $objectId = null);

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function newStandard()
    {
        return $this->processModalAction('new');
    }

    /**
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse
     */
    public function editStandard($objectId, $ignorePost = false)
    {
        return $this->processModalAction('edit', $objectId);
    }

    /**
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteStandard($objectId)
    {
        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() || !$this->checkActionPermission('delete', $objectId, $objectId)) {
            return $this->modalAccessDenied();
        }

        $dataArray = ['success' => 0];
        if ($this->request->getMethod() == 'POST') {
            $session         = $this->get('session');
            $formData        = $session->get($this->getSessionBase().'.data', []);
            $deletedFormData = $session->get($this->getSessionBase().'.data.deleted', []);
            if (is_array($formData) && $data = isset($formData[$objectId]) ? $formData[$objectId] : false) {
                $deletedFormData[$objectId] = $formData[$objectId];
                unset($formData[$objectId]);
                $session->set($this->getSessionBase($objectId).'.data', $formData);
                $session->set($this->getSessionBase($objectId).'.data.deleted', $deletedFormData);
            }

            $dataArray = [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'success'       => 1,
                'route'         => false,
                'objectId'      => $objectId,
                'deleted'       => 1,
                'data'          => $data,
            ];
        }

        return new JsonResponse($this->getResponseVars($dataArray));
    }

    /**
     * @param $objectId
     *
     * @return JsonResponse
     */
    public function undeleteAction($objectId)
    {
        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() || !$this->checkActionPermission('delete', $objectId, $objectId)) {
            return $this->modalAccessDenied();
        }

        $dataArray = ['success' => 0];
        if ($this->request->getMethod() == 'POST') {
            $session         = $this->get('session');
            $formData        = $session->get($this->getSessionBase().'.data', []);
            $deletedFormData = $session->get($this->getSessionBase().'.data.deleted', []);
            if (is_array($formData) && $data = isset($deletedFormData[$objectId]) ? $deletedFormData[$objectId] : false) {
                $formData[$objectId] = $deletedFormData[$objectId];
                unset($deletedFormData[$objectId]);
                $session->set($this->getSessionBase($objectId).'.data', $formData);
                $session->set($this->getSessionBase($objectId).'.data.deleted', $deletedFormData);
            }

            $dataArray = [
                'mauticContent' => $this->getJsLoadMethodPrefix(),
                'success'       => 1,
                'route'         => false,
                'objectId'      => $objectId,
                'undeleted'     => 1,
                'data'          => $data,
            ];
        }

        return new JsonResponse($dataArray);
    }

    /**
     * @param $args
     * @param $action
     *
     * @return mixed
     */
    protected function getResponseVars($args, $action)
    {
        return $args;
    }

    /**
     * @param      $action
     * @param null $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function processModalAction($action, $objectId = null)
    {
        $valid  = $cancelled  = false;
        $method = $this->request->getMethod();
        $data   = $this->getFormData($action, $objectId);

        if (false === $data || !$this->checkActionPermission($action, $data, $objectId)) {
            return $this->modalAccessDenied();
        }

        $form = $this->getActionForm($action, $data, $objectId);

        if (!$form instanceof Form) {
            throw new \InvalidArgumentException('getActionForm must return a '.Form::class.' object');
        }

        $this->beforeFormProcessed($data, $form, $action, 'POST' === $method);

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    if (!$this->processFormData($data, $form, $action, $objectId)) {
                        $valid = false;
                    }
                }

                $this->afterFormProcessed($valid, $data, $form, $action, $objectId);
            }
        }

        $passthroughVars = [
            'mauticContent'      => $this->getJsLoadMethodPrefix(),
            'success'            => $valid,
            'route'              => false,
            'closeModal'         => ($cancelled || $valid),
            'updateModalContent' => true,
            'objectId'           => $objectId,
        ];

        return $this->ajaxAction(
            [
                'contentTemplate' => $this->getTemplateName('form.html.php'),
                'viewParameters'  => $this->getViewArguments(
                    [
                        'form'     => $this->getFormView($form, $action),
                        'data'     => $data,
                        'objectId' => $objectId,
                    ],
                    $action
                ),
                'passthroughVars' => $this->getResponseVars($passthroughVars, $action),
            ]
        );
    }

    /**
     * @param $objectId
     */
    protected function clearSessionFormData($objectId)
    {
        $this->get('session')->remove($this->getSessionBase($objectId).'.data');
        $this->get('session')->remove($this->getSessionBase($objectId).'.data.deleted');
    }

    /**
     * @param      $objectId
     * @param bool $includeDeleted
     *
     * @return array|mixed
     */
    protected function getSessionData($objectId, $includeDeleted = false)
    {
        $data = $this->get('session')->get($this->getSessionBase($objectId).'.data', []);

        if (!$includeDeleted) {
            return $data;
        }

        $deleted = $this->get('session')->get($this->getSessionBase($objectId).'.data.deleted', []);

        return [$data, $deleted];
    }

    /**
     * @param      $isValid
     * @param      $data
     * @param Form $form
     * @param      $action
     * @param bool $objectId
     */
    protected function afterFormProcessed($isValid, $data, Form $form, $action, $objectId = false)
    {
        if ($id = (is_array($data) && isset($data['id'])) ? $data['id'] : $objectId) {
            if (is_object($data) && method_exists($data, 'convertToArray')) {
                $data = $data->convertToArray();
            }

            $session          = $this->get('session');
            $sessionData      = $session->get($this->getSessionBase($id).'.data', []);
            $sessionData[$id] = $data;
            $session->set($this->getSessionBase().'.data', $sessionData);
        }

        parent::afterFormProcessed($isValid, $data, $form, $action);
    }
}
