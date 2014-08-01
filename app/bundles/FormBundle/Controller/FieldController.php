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
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FieldController extends CommonFormController
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
            $formField = $this->request->request->get('formfield');
            $formField['alias'] = 'new';
            $fieldType = $formField['type'];
        } else {
            $fieldType = $this->request->query->get('type');
            $formField = array('type' => $fieldType, 'alias' => 'new');
        }

        //ajax only for form fields
        if (!$fieldType ||
            !$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        //fire the form builder event
        $customComponents = $this->factory->getModel('form.form')->getCustomComponents();

        $customParams = (isset($customComponents['fields'][$fieldType])) ? $customComponents['fields'][$fieldType] : false;

        $form = $this->get('form.factory')->create('formfield', $formField, array(
            'action'           => $this->generateUrl('mautic_formfield_action', array('objectAction' => 'new')),
            'customParameters' => $customParams
        ));

        if (!empty($customParams)) {
            $formField['isCustom']         = true;
            $formField['customParameters'] = $customParams;
        }

        //Check for a submitted form and process it
        if ($method == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    //form is valid so process the data

                    $keyId = 'new' . uniqid();

                    //save the properties to session
                    $fields          = $session->get('mautic.formfields.add');
                    $formData        = $form->getData();
                    $formField       = array_merge($formField, $formData);
                    $formField['id'] = $keyId;
                    $fields[$keyId]  = $formField;
                    $session->set('mautic.formfields.add', $fields);

                    //take note if this is a submit button or not
                    if ($fieldType == 'button' && $formField['properties']['type'] == 'submit') {
                        $submits = $session->get('mautic.formfields.submits', array());
                        $submits[] = $keyId;
                        $session->set('mautic.formfields.submits', $submits);
                    }
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = array('type' => $fieldType);
        if ($cancelled || $valid) {
            $tmpl = 'components';

            $fieldHelper = new FormFieldHelper($this->get('translator'));
            $viewParams['fields']  = $fieldHelper->getList($customComponents['fields']);
            $viewParams['actions'] = $customComponents['actions'];
        } else {
            $tmpl     = 'field';
            $formView = $form->createView();
            $this->get('templating')->getEngine('MauticFormBundle:Form:index.html.php')->get('form')
                ->setTheme($formView, 'MauticFormBundle:FormComponent');
            $viewParams['form'] = $formView;
            $header = (!empty($customParams)) ? $customParams['label'] : 'mautic.form.field.type.'.$fieldType;
            $viewParams['fieldHeader'] = $this->get('translator')->trans($header);
        }
        $viewParams['tmpl'] = $tmpl;

        $passthroughVars = array(
            'mauticContent' => 'formfield',
            'success'       => $success,
            'target'        => '.bundle-side-inner-wrapper',
            'route'         => false
        );

        if (!empty($keyId) ) {
            //prevent undefined errors
            $entity    = new Field();
            $blank     = $entity->convertToArray();
            $formField = array_merge($blank, $formField);

            $passthroughVars['fieldId']   = $keyId;
            $template = (!empty($customParams)) ? $customParams['template'] : 'MauticFormBundle:Field:' . $fieldType . '.html.php';
            $passthroughVars['fieldHtml'] = $this->renderView($template, array(
                'inForm' => true,
                'field'  => $formField,
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
        $session   = $this->factory->getSession();
        $method    = $this->request->getMethod();
        $fields    = $session->get('mautic.formfields.add', array());
        $success   = 0;
        $valid     = $cancelled = false;
        $formField = (array_key_exists($objectId, $fields)) ? $fields[$objectId] : null;

        if ($formField !== null) {
            $fieldType  = $formField['type'];

            //ajax only for form fields
            if (!$fieldType ||
                !$this->request->isXmlHttpRequest() ||
                !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
            ) {
                return $this->accessDenied();
            }

            //set custom params from event if applicable
            $customParams = (!empty($formField['isCustom'])) ? $formField['customParameters'] : array();

            $form = $this->get('form.factory')->create('formfield', $formField, array(
                'action'           => $this->generateUrl('mautic_formfield_action', array('objectAction' => 'edit', 'objectId' => $objectId)),
                'customParameters' => $customParams
            ));

            //Check for a submitted form and process it
            if ($method == 'POST') {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        //form is valid so process the data

                        //save the properties to session
                        $session           = $this->factory->getSession();
                        $fields            = $session->get('mautic.formfields.add');
                        $formData          = $form->getData();
                        //overwrite with updated data
                        $formField         = $fields[$objectId] = array_merge($fields[$objectId], $formData);
                        $session->set('mautic.formfields.add', $fields);

                        //take note if this is a submit button or not
                        if ($fieldType == 'button') {
                            $submits = $session->get('mautic.formfields.submits', array());
                            if ($formField['properties']['type'] == 'submit' && !in_array($objectId, $submits)) {
                                //button type updated to submit
                                $submits[] = $objectId;
                                $session->set('mautic.formfields.submits', $submits);
                            } elseif ($formField['properties']['type'] != 'submit' && in_array($objectId, $submits)) {
                                //button type updated to something other than submit
                                $key = array_search($objectId, $submits);
                                unset($submits[$key]);
                                $session->set('mautic.formfields.submits', $submits);
                            }
                        }
                    }
                }
            }

            $viewParams = array('type' => $fieldType);
            if ($cancelled || $valid) {
                $tmpl = 'components';

                //fire the form builder event
                $customComponents = $this->factory->getModel('form.form')->getCustomComponents();

                $fieldHelper = new FormFieldHelper($this->get('translator'));
                $viewParams['fields']  = $fieldHelper->getList($customComponents['fields']);
                $viewParams['actions'] = $customComponents['actions'];
            } else {
                $tmpl     = 'field';
                $formView = $form->createView();
                $this->get('templating')->getEngine('MauticFormBundle:Form:index.html.php')->get('form')
                    ->setTheme($formView, 'MauticFormBundle:FormComponent');
                $viewParams['form'] = $formView;
                $header = (!empty($customParams)) ? $customParams['label'] : 'mautic.form.field.type.'.$fieldType;
                $viewParams['fieldHeader'] = $this->get('translator')->trans($header);
            }
            $viewParams['tmpl'] = $tmpl;

            $passthroughVars = array(
                'mauticContent' => 'formfield',
                'success'       => $success,
                'target'        => '.bundle-side-inner-wrapper',
                'route'         => false
            );

            $passthroughVars['fieldId'] = $objectId;
            if (!empty($customParams)) {
                $template = $customParams['template'];
            } else {
                $template = 'MauticFormBundle:Field:' . $fieldType . '.html.php';
            }

            //prevent undefined errors
            $entity    = new Field();
            $blank     = $entity->convertToArray();
            $formField = array_merge($blank, $formField);

            $passthroughVars['fieldHtml'] = $this->renderView($template, array(
                'inForm' => true,
                'field'  => $formField,
                'id'     => $objectId
            ));

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
        $fields    = $session->get('mautic.formfields.add', array());
        $delete    = $session->get('mautic.formfields.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ){
            return $this->accessDenied();
        }

        $formField = (array_key_exists($objectId, $fields)) ? $fields[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $formField !== null) {
            //set custom params from event if applicable
            $customParams = (!empty($formField['isCustom'])) ? $formField['customParameters'] : array();

            //add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.formfields.remove', $delete);
            }

            //take note if this is a submit button or not
            if ($formField['type'] == 'button') {
                $submits    = $session->get('mautic.formfields.submits', array());
                $properties = $formField['properties'];
                if ($properties['type'] == 'submit' && in_array($objectId, $submits)) {
                    $key = array_search($objectId, $submits);
                    unset($submits[$key]);
                    $session->set('mautic.formfields.submits', $submits);
                }
            }

            if (!empty($customParams)) {
                $template = $customParams['template'];
            } else {
                $template = 'MauticFormBundle:Field:' . $formField['type'] . '.html.php';
            }

            //prevent undefined errors
            $entity    = new Field();
            $blank     = $entity->convertToArray();
            $formField = array_merge($blank, $formField);

            $dataArray  = array(
                'mauticContent'  => 'formfield',
                'success'        => 1,
                'target'         => '#mauticform_'.$objectId,
                'route'          => false,
                'fieldId'        => $objectId,
                'replaceContent' => true,
                'fieldHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'field'   => $formField,
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
        $fields    = $session->get('mautic.formfields.add', array());
        $delete    = $session->get('mautic.formfields.remove', array());

        //ajax only for form fields
        if (!$this->request->isXmlHttpRequest() ||
            !$this->factory->getSecurity()->isGranted(array('form:forms:editown', 'form:forms:editother', 'form:forms:create'), 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $formField = (array_key_exists($objectId, $fields)) ? $fields[$objectId] : null;

        if ($this->request->getMethod() == 'POST' && $formField !== null) {
            //set custom params from event if applicable
            $customParams = (!empty($formField['isCustom'])) ? $formField['customParameters'] : array();

            //add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.formfields.remove', $delete);
            }

            //take note if this is a submit button or not
            if ($formField['type'] == 'button') {
                $properties = $formField['properties'];
                if ($properties['type'] == 'submit') {
                    $submits   = $session->get('mautic.formfields.submits', array());
                    $submits[] = $objectId;
                    $session->set('mautic.formfields.submits', $submits);
                }
            }

            if (!empty($customParams)) {
                $template = $customParams['template'];
            } else {
                $template = 'MauticFormBundle:Field:' . $formField['type'] . '.html.php';
            }

            //prevent undefined errors
            $entity    = new Field();
            $blank     = $entity->convertToArray();
            $formField = array_merge($blank, $formField);

            $dataArray  = array(
                'mauticContent'  => 'formfield',
                'success'        => 1,
                'target'         => '#mauticform_'.$objectId,
                'route'          => false,
                'fieldId'        => $objectId,
                'replaceContent' => true,
                'fieldHtml'      => $this->renderView($template, array(
                    'inForm'  => true,
                    'field'   => $formField,
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