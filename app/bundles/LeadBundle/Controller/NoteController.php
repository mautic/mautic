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
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\Form\FormError;

class NoteController extends FormController
{

    /**
     * Generate's default list view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->factory->getSecurity()->isGranted('lead:notes:full')) {
            return $this->accessDenied();
        }
        $items = $this->factory->getModel('lead.notes')->getEntities();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'      => $items
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:index.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadnote_index',
                'route'         => $this->generateUrl('mautic_leadnote_index'),
                'mauticContent' => 'leadnote'
            )
        ));
    }

    /**
     * Generate's new note and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->factory->getSecurity()->isGranted('lead:notes:full')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $note       = new LeadNote();
        $model      = $this->factory->getModel('lead.note');
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_leadnote_index');
        $action     = $this->generateUrl('mautic_leadnote_action', array('objectAction' => 'new'));
        //get the user form factory
        $form       = $model->createForm($note, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $request = $this->request->request->all();
                    if (isset($request['leadnote']['properties'])) {
                        $result = $model->setNoteProperties($note, $request['leadnote']['properties']);
                        if ($result !== true) {
                            //set the error
                            $form->get('properties')->addError(new FormError(
                                $this->get('translator')->trans($result, array(), 'validators')
                            ));
                            $valid = false;
                        }
                    }

                    if ($valid) {
                        //form is valid so process the data
                        $model->saveEntity($note);

                        // $this->request->getSession()->getFlashBag()->add(
                        //     'notice',
                        //     $this->get('translator')->trans('mautic.lead.note.notice.created',  array(
                        //         '%name%' => $note->getName(),
                        //         '%url%'          => $this->generateUrl('mautic_leadnote_action', array(
                        //             'objectAction' => 'edit',
                        //             'objectId'     => $note->getId()
                        //         ))
                        //     ), 'flashes')
                        // );
                    }
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'contentTemplate' => 'MauticLeadBundle:Note:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_leadnote_index',
                        'mauticContent' => 'leadnote'
                    )
                ));
            } elseif (!$cancelled) {
                return $this->editAction($note->getId(), true);
            }
        }

        $formView = $this->setFormTheme($form, 'MauticLeadBundle:Note:form.html.php', 'MauticLeadBundle:FormNote');

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $form->createView()
            ),
            'contentTemplate' => 'MauticLeadBundle:Note:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadnote_index',
                'route'         => $this->generateUrl('mautic_leadnote_action', array('objectAction' => 'new')),
                'mauticContent' => 'leadnote'
            )
        ));
    }

    // /**
    //  * Generate's edit form and processes post data
    //  *
    //  * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    //  */
    // public function editAction ($objectId, $ignorePost = false)
    // {
    //     if (!$this->factory->getSecurity()->isGranted('lead:fields:full')) {
    //         return $this->accessDenied();
    //     }

    //     $model   = $this->factory->getModel('lead.field');
    //     $field   = $model->getEntity($objectId);

    //     //set the return URL
    //     $returnUrl  = $this->generateUrl('mautic_leadfield_index');

    //     $postActionVars = array(
    //         'returnUrl'       => $returnUrl,
    //         'contentTemplate' => 'MauticLeadBundle:Field:index',
    //         'passthroughVars' => array(
    //             'activeLink'    => '#mautic_leadfield_index',
    //             'mauticContent' => 'leadfield'
    //         )
    //     );
    //     //list not found
    //     if ($field === null) {
    //         return $this->postActionRedirect(
    //             array_merge($postActionVars, array(
    //                 'flashes' => array(
    //                     array(
    //                         'type' => 'error',
    //                         'msg'  => 'mautic.lead.field.error.notfound',
    //                         'msgVars' => array('%id%' => $objectId)
    //                     )
    //                 )
    //             ))
    //         );
    //     } elseif ($model->isLocked($field)) {
    //         //deny access if the entity is locked
    //         return $this->isLocked($postActionVars, $field, 'lead.field');
    //     }

    //     $action = $this->generateUrl('mautic_leadfield_action', array('objectAction' => 'edit', 'objectId' => $objectId));
    //     $form   = $model->createForm($field, $this->get('form.factory'), $action);

    //     ///Check for a submitted form and process it
    //     if (!$ignorePost && $this->request->getMethod() == 'POST') {
    //         $valid = false;
    //         if (!$cancelled = $this->isFormCancelled($form)) {
    //             if ($valid = $this->isFormValid($form)) {
    //                 $request = $this->request->request->all();
    //                 if (isset($request['leadfield']['properties'])) {
    //                     $result = $model->setFieldProperties($field, $request['leadfield']['properties']);
    //                     if ($result !== true) {
    //                         //set the error
    //                         $form->get('properties')->addError(new FormError(
    //                             $this->get('translator')->trans($result, array(), 'validators')
    //                         ));
    //                         $valid = false;
    //                     }
    //                 }

    //                 if ($valid) {
    //                     //form is valid so process the data
    //                     $model->saveEntity($field, $form->get('buttons')->get('save')->isClicked());

    //                     $this->request->getSession()->getFlashBag()->add(
    //                         'notice',
    //                         $this->get('translator')->trans('mautic.lead.field.notice.created',  array(
    //                             '%name%' => $field->getLabel(),
    //                             '%url%'          => $this->generateUrl('mautic_leadfield_action', array(
    //                                 'objectAction' => 'edit',
    //                                 'objectId'     => $field->getId()
    //                             ))
    //                         ), 'flashes')
    //                     );
    //                 }
    //             }
    //         } else {
    //             //unlock the entity
    //             $model->unlockEntity($field);
    //         }

    //         if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
    //             return $this->postActionRedirect(
    //                 array_merge($postActionVars, array(
    //                         'viewParameters'  => array('objectId' => $field->getId()),
    //                         'contentTemplate' => 'MauticLeadBundle:Field:index'
    //                     )
    //                 )
    //             );
    //         }
    //     } else {
    //         //lock the entity
    //         $model->lockEntity($field);
    //     }

    //     return $this->delegateView(array(
    //         'viewParameters'  => array(
    //             'form'    => $form->createView()
    //         ),
    //         'contentTemplate' => 'MauticLeadBundle:Field:form.html.php',
    //         'passthroughVars' => array(
    //             'activeLink'    => '#mautic_leadfield_index',
    //             'route'         => $action,
    //             'mauticContent' => 'leadfield'
    //         )
    //     ));
    // }

    // /**
    //  * Clone an entity
    //  *
    //  * @param $objectId
    //  * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
    //  */
    // public function cloneAction ($objectId)
    // {
    //     $model   = $this->factory->getModel('lead.field');
    //     $entity  = $model->getEntity($objectId);

    //     if ($entity != null) {
    //         if (!$this->factory->getSecurity()->isGranted('lead:fields:full')) {
    //             return $this->accessDenied();
    //         }

    //         $clone = clone $entity;
    //         $clone->setIsPublished(false);
    //         $clone->setIsFixed(false);
    //         $model->saveEntity($clone);
    //         $objectId = $clone->getId();
    //     }

    //     return $this->editAction($objectId);
    // }

    // /**
    //  * Delete a field
    //  *
    //  * @param         $objectId
    //  * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
    //  */
    // public function deleteAction($objectId)
    // {
    //     if (!$this->factory->getSecurity()->isGranted('lead:fields:full')) {
    //         return $this->accessDenied();
    //     }

    //     $returnUrl = $this->generateUrl('mautic_leadfield_index');
    //     $flashes   = array();

    //     $postActionVars = array(
    //         'returnUrl'       => $returnUrl,
    //         'contentTemplate' => 'MauticLeadBundle:Field:index',
    //         'passthroughVars' => array(
    //             'activeLink'    => '#mautic_leadfield_index',
    //             'mauticContent' => 'lead'
    //         )
    //     );

    //     if ($this->request->getMethod() == 'POST') {
    //         $model  = $this->factory->getModel('lead.field');
    //         $field = $model->getEntity($objectId);

    //         if ($field === null) {
    //             $flashes[] = array(
    //                 'type'    => 'error',
    //                 'msg'     => 'mautic.lead.field.error.notfound',
    //                 'msgVars' => array('%id%' => $objectId)
    //             );
    //         } elseif ($model->isLocked($field)) {
    //             return $this->isLocked($postActionVars, $field, 'lead.field');
    //         } elseif ($field->isFixed()) {
    //             //cannot delete fixed fields
    //             return $this->accessDenied();
    //         }

    //         $model->deleteEntity($field);

    //         $flashes[]  = array(
    //             'type'    => 'notice',
    //             'msg'     => 'mautic.lead.field.notice.deleted',
    //             'msgVars' => array(
    //                 '%name%' => $field->getLabel(),
    //                 '%id%'   => $objectId
    //             )
    //         );
    //     } //else don't do anything

    //     return $this->postActionRedirect(
    //         array_merge($postActionVars, array(
    //             'flashes' => $flashes
    //         ))
    //     );
    // }
}