<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ActionController extends CommonFormController
{

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $success     = 0;
        $valid       = $cancelled = false;
        $method      = $this->request->getMethod();
        $session     = $this->factory->getSession();

        if ($method == 'POST') {
            $formAction = $this->request->request->get('formaction');
            $actionType = $formAction['type'];
        } else {
            $actionType = $this->request->query->get('type');
            $formAction = array('type' => $actionType);
        }

        //ajax only for form fields
        if (!$actionType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the form builder event
        $customComponents = $this->factory->getModel('form.form')->getCustomComponents();
        $form = $this->get('form.factory')->create('formaction', $formAction, array(
            'action'    => $this->generateUrl('mautic_formaction_action', array('objectAction' => 'new')),
            'settings'  => $customComponents['actions'][$actionType]
        ));

        $formAction['settings'] = $customComponents['actions'][$actionType];

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data
                    $keyId = 'new' . uniqid();

                    //save the properties to session
                    $actions          = $session->get('mautic.formactions.add');
                    $formData         = $form->getData();
                    $formAction       = array_merge($formAction, $formData);
                    $formAction['id'] = $keyId;
                    if (empty($formAction['name'])) {
                        //set it to the event default
                        $formAction['name'] = $this->get('translator')->trans($formAction['settings']['label']);
                    }
                    $actions[$keyId]  = $formAction;
                    $session->set('mautic.formactions.add', $actions);
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $actionType);
        if ($cancelled || $valid) {
            $tmpl = 'components';

            $fieldHelper = new FormFieldHelper($this->get('translator'));
            $viewParams['fields']   = $fieldHelper->getList($customComponents['fields']);
            $viewParams['actions']  = $customComponents['actions'];
            $viewParams['expanded'] = 'actions';

        } else {
            $tmpl     = 'action';
            $formView = $form->createView();
            $this->get('templating')->getEngine('MauticFormBundle:Form:index.html.php')->get('form')
                ->setTheme($formView, 'MauticFormBundle:FormComponent');
            $viewParams['form'] = $formView;
            $header = $formAction['settings']['label'];
            $viewParams['fieldHeader'] = $this->get('translator')->trans($header);
        }
        $viewParams['tmpl'] = $tmpl;

        $passthroughVars = array(
            'mauticContent' => 'formaction',
            'success'       => $success,
            'target'        => '.bundle-side-inner-wrapper',
            'route'         => false
        );

        if (!empty($keyId) ) {
            //prevent undefined errors
            $entity    = new Action();
            $blank     = $entity->convertToArray();
            $formAction = array_merge($blank, $formAction);

            $template = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                'MauticFormBundle:Action:generic.html.php';
            $passthroughVars['actionId']   = $keyId;
            $passthroughVars['actionHtml'] = $this->renderView($template, array(
                'inForm' => true,
                'action' => $formAction,
                'id'     => $keyId
            ));
        }

        return $this->ajaxAction(array(
            'contentTemplate' => 'MauticFormBundle:Builder:' . $tmpl . '.html.php',
            'viewParameters'  => $viewParams,
            'passthroughVars' => $passthroughVars
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        $session    = $this->factory->getSession();
        $method     = $this->request->getMethod();
        $actions    = $session->get('mautic.formactions.add', array());
        $success    = 0;
        $valid      = $cancelled = false;
        $formAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($formAction !== null) {
            $actionType  = $formAction['type'];

            //ajax only for form fields
            if (!$actionType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            $form = $this->get('form.factory')->create('formaction', $formAction, array(
                'action'   => $this->generateUrl('mautic_formaction_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
                'settings' => $formAction['settings']
            ));

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //form is valid so process the data

                        //save the properties to session
                        $session           = $this->factory->getSession();
                        $actions           = $session->get('mautic.formactions.add');
                        $formData          = $form->getData();
                        //overwrite with updated data
                        $formAction        = array_merge($actions[$objectId], $formData);
                        if (empty($formAction['name'])) {
                            //set it to the event default
                            $formAction['name'] = $this->get('translator')->trans($formAction['settings']['label']);
                        }
                        $actions[$objectId] = $formAction;
                        $session->set('mautic.formactions.add', $actions);

                        //generate HTML for the field
                        $keyId = $objectId;

                        //take note if this is a submit button or not
                        if ($actionType == 'button') {
                            $submits = $session->get('mautic.formactions.submits', array());
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

            $viewParams = array('type' => $actionType);
            if ($cancelled || $valid) {
                $tmpl = 'components';

                //fire the form builder event
                $customComponents = $this->factory->getModel('form.form')->getCustomComponents();

                $fieldHelper = new FormFieldHelper($this->get('translator'));
                $viewParams['fields']   = $fieldHelper->getList($customComponents['fields']);
                $viewParams['actions']  = $customComponents['actions'];
                $viewParams['expanded'] = 'actions';
            } else {
                $tmpl     = 'action';
                $formView = $form->createView();
                $this->get('templating')->getEngine('MauticFormBundle:Form:index.html.php')->get('form')
                    ->setTheme($formView, 'MauticFormBundle:FormComponent');
                $viewParams['form']        = $formView;
                $viewParams['fieldHeader'] = $this->get('translator')->trans($formAction['settings']['label']);
            }
            $viewParams['tmpl'] = $tmpl;

            $passthroughVars = array(
                'mauticContent' => 'formaction',
                'success'       => $success,
                'target'        => '.bundle-side-inner-wrapper',
                'route'         => false
            );

            if (!empty($keyId)) {
                $passthroughVars['actionId'] = $keyId;

                //prevent undefined errors
                $entity     = new Action();
                $blank      = $entity->convertToArray();
                $formAction = array_merge($blank, $formAction);
                $template   = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                    'MauticFormBundle:Action:generic.html.php';
                $passthroughVars['actionHtml'] = $this->renderView($template, array(
                    'inForm' => true,
                    'action'  => $formAction,
                    'id'     => $keyId
                ));
            }

            return $this->ajaxAction(array(
                'contentTemplate' => 'MauticFormBundle:Builder:' . $tmpl . '.html.php',
                'viewParameters'  => $viewParams,
                'passthroughVars' => $passthroughVars
            ));
        } else {
            $response  = new JsonResponse(array('success' => 0));
            $response->headers->set('Content-Length', strlen($response->getContent()));
            return $response;
        }
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        $session   = $this->factory->getSession();
        $actions   = $session->get('mautic.formactions.add', array());
        $delete    = $session->get('mautic.formactions.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ){
            return $this->accessDenied();
        }

        $formAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $formAction !== null) {
            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.formactions.remove', $delete);
            }

            //take note if this is a submit button or not
            if ($formAction['type'] == 'button') {
                $submits    = $session->get('mautic.formactions.submits', array());
                $properties = $formAction['properties'];
                if ($properties['type'] == 'submit' && in_array($objectId, $submits)) {
                    $key = array_search($objectId, $submits);
                    unset($submits[$key]);
                    $session->set('mautic.formactions.submits', $submits);
                }
            }

            $template = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                'MauticFormBundle:Action:generic.html.php';

            //prevent undefined errors
            $entity    = new Action();
            $blank     = $entity->convertToArray();
            $formAction = array_merge($blank, $formAction);

            $dataArray  = array(
                'mauticContent'  => 'formaction',
                'success'        => 1,
                'target'         => '#mauticform_'.$objectId,
                'route'          => false,
                'actionId'        => $objectId,
                'replaceContent' => true,
                'actionHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'action'  => $formAction,
                    'id'      => $objectId,
                    'deleted' => true
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }

    /**
     * Undeletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function undeleteAction($objectId) {
        $session   = $this->factory->getSession();
        $actions    = $session->get('mautic.formactions.add', array());
        $delete    = $session->get('mautic.formactions.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $formAction = (array_key_exists($objectId, $actions)) ? $actions[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $formAction !== null) {

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.formactions.remove', $delete);
            }

            //take note if this is a submit button or not
            if ($formAction['type'] == 'button') {
                $properties = $formAction['properties'];
                if ($properties['type'] == 'submit') {
                    $submits   = $session->get('mautic.formactions.submits', array());
                    $submits[] = $objectId;
                    $session->set('mautic.formactions.submits', $submits);
                }
            }

            $template = (!empty($formAction['settings']['template'])) ? $formAction['settings']['template'] :
                'MauticFormBundle:Action:generic.html.php';

            //prevent undefined errors
            $entity     = new Action();
            $blank      = $entity->convertToArray();
            $formAction = array_merge($blank, $formAction);

            $dataArray  = array(
                'mauticContent'  => 'formaction',
                'success'        => 1,
                'target'         => '#mauticform_'.$objectId,
                'route'          => false,
                'actionId'        => $objectId,
                'replaceContent' => true,
                'actionHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'action'   => $formAction,
                    'id'      => $objectId,
                    'deleted' => false
                ))
            );
        } else {
            $dataArray = array('success' => 0);
        }

        $response  = new JsonResponse($dataArray);
        $response->headers->set('Content-Length', strlen($response->getContent()));
        return $response;
    }
}