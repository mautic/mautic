<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FieldController extends FormController
{

    /**
     * Generate's default list view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $items = $this->container->get('mautic.model.leadfield')->getEntities();

        return $this->delegateView(array(
            'viewParameters'  => array('items' => $items),
            'contentTemplate' => 'MauticLeadBundle:Field:index.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadfield_index',
                'route'         => $this->generateUrl('mautic_leadfield_index'),
                'mauticContent' => 'leadfield'
            )
        ));
    }

    /**
     * Generate's new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $field     = new LeadField();
        $model      = $this->container->get('mautic.model.leadfield');
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_leadfield_index');
        $action     = $this->generateUrl('mautic_leadfield_action', array('objectAction' => 'new'));
        //get the user form factory
        $form       = $model->createForm($field, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                $request = $this->request->request->all();
                if (isset($request['leadfield']['definitions'])) {
                    $result = $model->setFieldDefinitions($field, $request['leadfield']['definitions']);
                    if ($result !== true) {
                        //set the error
                        $form->get('definitions')->addError(new FormError(
                            $this->get('translator')->trans($result, array(), 'validators')
                        ));
                        $valid = 0;
                    }
                }

                if ($valid) {
                    //form is valid so process the data
                    $model->saveEntity($field);
                }
            }

            if (!empty($valid)) { //cancelled or success
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticLeadBundle:Field:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_leadfield_index',
                        'mauticContent' => 'leadfield'
                    ),
                    'flashes'         =>
                        ($valid === 1) ? array(
                            array(
                                'type'    => 'notice',
                                'msg'     => 'mautic.lead.field.notice.created',
                                'msgVars' => array('%name%' => $field->getLabel())
                            )
                        ) : array()
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $form->createView()
            ),
            'contentTemplate' => 'MauticLeadBundle:Field:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadfield_index',
                'route'         => $this->generateUrl('mautic_leadfield_action', array('objectAction' => 'new')),
                'mauticContent' => 'leadfield'
            )
        ));
    }

    /**
     * Generate's edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $model   = $this->container->get('mautic.model.leadfield');
        $field   = $model->getEntity($objectId);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_leadfield_index');

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Field:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadfield_index',
                'mauticContent' => 'leadfield'
            )
        );
        //list not found
        if ($field === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.lead.field.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif ($model->isLocked($field)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $field, 'leadfield');
        }

        $action = $this->generateUrl('mautic_leadfield_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($field, $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = $this->checkFormValidity($form);

            if ($valid === 1) {
                $request = $this->request->request->all();
                if (isset($request['leadfield']['definitions'])) {
                    $result = $model->setFieldDefinitions($field, $request['leadfield']['definitions']);
                    if ($result !== true) {
                        //set the error
                        $form->get('definitions')->addError(new FormError(
                            $this->get('translator')->trans($result, array(), 'validators')
                        ));
                        $valid = 0;
                    }
                }

                if ($valid) {
                    //form is valid so process the data
                    $model->saveEntity($field);
                }
            }

            if (!empty($valid)) { //cancelled or success
                if ($valid === -1) {
                    //unlock the entity
                    $model->unlockEntity($field);
                }

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                            'viewParameters'  => array('objectId' => $field->getId()),
                            'contentTemplate' => 'MauticLeadBundle:Field:index',
                            'flashes'         =>
                                ($valid === 1) ? array( //success
                                    array(
                                        'type' => 'notice',
                                        'msg'  => 'mautic.lead.field.notice.updated',
                                        'msgVars' => array('%name%' => $field->getLabel())
                                    )
                                ) : array()
                        )
                    )
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($field);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'    => $form->createView()
            ),
            'contentTemplate' => 'MauticLeadBundle:Field:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadfield_index',
                'route'         => $action,
                'mauticContent' => 'leadfield'
            )
        ));
    }

    /**
     * Delete a field
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_leadfield_index');
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Field:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadfield_index',
                'mauticContent' => 'lead'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->container->get('mautic.model.leadfield');
            $field = $model->getEntity($objectId);

            if ($field === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.field.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif ($model->isLocked($field)) {
                return $this->isLocked($postActionVars, $field);
            } elseif ($field->isFixed()) {
                //cannot delete fixed fields
                return $this->accessDenied();
            }

            $model->deleteEntity($field);

            $flashes[]  = array(
                'type'    => 'notice',
                'msg'     => 'mautic.lead.field.notice.deleted',
                'msgVars' => array(
                    '%name%' => $field->getLabel(),
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * {@inheritdoc)
     *
     * @param $action
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function executeAjaxAction( Request $request, $ajaxAction = "" )
    {
        $dataArray = array("success" => 0);
        switch ($ajaxAction) {
            case "reorder":
                $fields = $this->request->request->get('field');
                if (!empty($fields)) {
                    $this->get('mautic.model.leadfield')->reorderFieldsByList($fields);
                    $dataArray['success'] = 1;
                }
                break;
        }
        $response  = new JsonResponse();
        $response->setData($dataArray);

        return $response;
    }
}