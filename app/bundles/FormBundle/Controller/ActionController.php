<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\FormBundle\Entity\Action;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ActionController.
 */
class ActionController extends CommonFormController
{
    /**
     * Generates new form and processes post data.
     *
     * @return JsonResponse
     */
    public function newAction()
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $method  = $this->request->getMethod();
        $session = $this->get('session');

        if ($method == 'POST') {
            $formAction = $this->request->request->get('formaction');
            $actionType = $formAction['type'];
            $formId     = $formAction['formId'];
        } else {
            $actionType = $this->request->query->get('type');
            $formId     = $this->request->query->get('formId');
            $formAction = [
                'type'   => $actionType,
                'formId' => $formId,
            ];
        }

        //ajax only for form fields
        if (!$actionType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->get('mautic.security')->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
        ) {
            return $this->modalAccessDenied();
        }

        //fire the form builder event
        $customComponents = $this->getModel('form.form')->getCustomComponents();
        $form             = $this->get('form.factory')->create('formaction', $formAction, [
            'action'   => $this->generateUrl('mautic_formaction_action', ['objectAction' => 'new']),
            'settings' => $customComponents['actions'][$actionType],
            'formId'   => $formId,
        ]);
        $form->get('formId')->setData($formId);
        $formAction['settings'] = $customComponents['actions'][$actionType];

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new'.hash('sha1', uniqid(mt_rand()));

                    //save the properties to session
                    $actions          = $session->get('mautic.form.'.$formId.'.actions.modified', []);
                    $formData         = $form->getData();
                    $formAction       = array_merge($formAction, $formData);
                    $formAction['id'] = $keyId;
                    if (empty($formAction['name'])) {
                        //set it to the event default
                        $formAction['name'] = $this->get('translator')->trans($formAction['settings']['label']);
                    }
                    $actions[$keyId] = $formAction;
                    $session->set('mautic.form.'.$formId.'.actions.modified', $actions);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = ['type' => $actionType];

        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal                 = false;
            $viewParams['tmpl']         = 'action';
            $viewParams['form']         = (isset($formAction['settings']['formTheme'])) ? $this->setFormTheme($form, 'MauticFormBundle:Builder:action.html.php', $formAction['settings']['formTheme']) : $form->createView();
            $header                     = $formAction['settings']['label'];
            $viewParams['actionHeader'] = $this->get('translator')->trans($header);
        }

        $passthroughVars = [
            'mauticContent' => 'formAction',
            'success'       => $success,
            'route'         => false,
        ];

        if (!empty($keyId)) {
            //prevent undefined errors
            $entity     = new Action();
            $blank      = $entity->convertToArray();
            $formAction = array_merge($blank, $formAction);

            $template = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                'MauticFormBundle:Action:generic.html.php';
            $passthroughVars['actionId']   = $keyId;
            $passthroughVars['actionHtml'] = $this->renderView($template, [
                'inForm' => true,
                'action' => $formAction,
                'id'     => $keyId,
                'formId' => $formId,
            ]);
        }

        if ($closeModal) {
            //just close the modal
            $passthroughVars['closeModal'] = 1;
            $response                      = new JsonResponse($passthroughVars);

            return $response;
        }

        return $this->ajaxAction([
            'contentTemplate' => 'MauticFormBundle:Builder:'.$viewParams['tmpl'].'.html.php',
            'viewParameters'  => $viewParams,
            'passthroughVars' => $passthroughVars,
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function editAction($objectId)
    {
        $session    = $this->get('session');
        $method     = $this->request->getMethod();
        $formId     = ($method == 'POST') ? $this->request->request->get('formaction[formId]', '', true) : $this->request->query->get('formId');
        $actions    = $session->get('mautic.form.'.$formId.'.actions.modified', []);
        $success    = 0;
        $valid      = $cancelled      = false;
        $formAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($formAction !== null) {
            $actionType             = $formAction['type'];
            $customComponents       = $this->getModel('form.form')->getCustomComponents();
            $formAction['settings'] = $customComponents['actions'][$actionType];

            //ajax only for form fields
            if (!$actionType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->get('mautic.security')->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
            ) {
                return $this->modalAccessDenied();
            }

            $form = $this->get('form.factory')->create('formaction', $formAction, [
                'action'   => $this->generateUrl('mautic_formaction_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                'settings' => $formAction['settings'],
                'formId'   => $formId,
            ]);
            $form->get('formId')->setData($formId);

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //form is valid so process the data

                        //save the properties to session
                        $session  = $this->get('session');
                        $actions  = $session->get('mautic.form.'.$formId.'.actions.modified');
                        $formData = $form->getData();
                        //overwrite with updated data
                        $formAction = array_merge($actions[$objectId], $formData);
                        if (empty($formAction['name'])) {
                            //set it to the event default
                            $formAction['name'] = $this->get('translator')->trans($formAction['settings']['label']);
                        }
                        $actions[$objectId] = $formAction;
                        $session->set('mautic.form.'.$formId.'.actions.modified', $actions);

                        //generate HTML for the field
                        $keyId = $objectId;

                        //take note if this is a submit button or not
                        if ($actionType == 'button') {
                            $submits = $session->get('mautic.formactions.submits', []);
                            if ($formAction['properties']['type'] == 'submit' && !in_array($keyId, $submits)) {
                                //button type updated to submit
                                $submits[] = $keyId;
                                $session->set('mautic.formactions.submits', $submits);
                            } elseif ($formAction['properties']['type'] != 'submit' && in_array($keyId, $submits)) {
                                //button type updated to something other than submit
                                $key = array_search($keyId, $submits);
                                unset($submits[$key]);
                                $session->set('mautic.formactions.submits', $submits);
                            }
                        }
                    }
                }
            }

            $viewParams = ['type' => $actionType];
            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                $closeModal                 = false;
                $viewParams['tmpl']         = 'action';
                $viewParams['form']         = (isset($formAction['settings']['formTheme'])) ? $this->setFormTheme($form, 'MauticFormBundle:Builder:action.html.php', $formAction['settings']['formTheme']) : $form->createView();
                $viewParams['actionHeader'] = $this->get('translator')->trans($formAction['settings']['label']);
            }

            $passthroughVars = [
                'mauticContent' => 'formAction',
                'success'       => $success,
                'route'         => false,
            ];

            if (!empty($keyId)) {
                $passthroughVars['actionId'] = $keyId;

                //prevent undefined errors
                $entity     = new Action();
                $blank      = $entity->convertToArray();
                $formAction = array_merge($blank, $formAction);
                $template   = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                    'MauticFormBundle:Action:generic.html.php';
                $passthroughVars['actionHtml'] = $this->renderView($template, [
                    'inForm' => true,
                    'action' => $formAction,
                    'id'     => $keyId,
                    'formId' => $formId,
                ]);
            }

            if ($closeModal) {
                //just close the modal
                $passthroughVars['closeModal'] = 1;
                $response                      = new JsonResponse($passthroughVars);

                return $response;
            }

            return $this->ajaxAction([
                'contentTemplate' => 'MauticFormBundle:Builder:'.$viewParams['tmpl'].'.html.php',
                'viewParameters'  => $viewParams,
                'passthroughVars' => $passthroughVars,
            ]);
        }

        $response = new JsonResponse(['success' => 0]);

        return $response;
    }

    /**
     * Deletes the entity.
     *
     * @param $objectId
     *
     * @return JsonResponse
     */
    public function deleteAction($objectId)
    {
        $session = $this->get('session');
        $formId  = $this->request->query->get('formId');
        $actions = $session->get('mautic.form.'.$formId.'.actions.modified', []);
        $delete  = $session->get('mautic.form.'.$formId.'.actions.deleted', []);

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->get('mautic.security')->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $formAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;
        if ($this->request->getMethod() == 'POST' && $formAction !== null) {
            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.form.'.$formId.'.actions.deleted', $delete);
            }

            //take note if this is a submit button or not
            if ($formAction['type'] == 'button') {
                $submits    = $session->get('mautic.formactions.submits', []);
                $properties = $formAction['properties'];
                if ($properties['type'] == 'submit' && in_array($objectId, $submits)) {
                    $key = array_search($objectId, $submits);
                    unset($submits[$key]);
                    $session->set('mautic.formactions.submits', $submits);
                }
            }

            $dataArray = [
                'mauticContent' => 'formAction',
                'success'       => 1,
                'route'         => false,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }
}
