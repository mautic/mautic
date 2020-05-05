<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\FormError;

class FieldController extends FormController
{
    /**
     * Generate's default list view.
     *
     * @param int $page
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(['lead:fields:full'], 'RETURN_ARRAY');

        $session = $this->get('session');

        if (!$permissions['lead:fields:full']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $limit  = $session->get('mautic.leadfield.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $search = $this->request->get('search', $session->get('mautic.leadfield.filter', ''));
        $session->set('mautic.leadfilter.filter', $search);

        //do some default filtering
        $orderBy    = $this->get('session')->get('mautic.leadfilter.orderby', 'f.order');
        $orderByDir = $this->get('session')->get('mautic.leadfilter.orderbydir', 'ASC');

        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $request = $this->factory->getRequest();
        $search  = $request->get('search', $session->get('mautic.lead.emailtoken.filter', ''));

        $session->set('mautic.lead.emailtoken.filter', $search);

        $fields = $this->getModel('lead.field')->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => ['string' => $search],
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);
        $count = count($fields);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.leadfield.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_contactfield_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'MauticLeadBundle:Field:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contactfield_index',
                    'mauticContent' => 'leadfield',
                ],
            ]);
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.leadfield.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView([
            'viewParameters' => [
                'items'       => $fields,
                'searchValue' => $search,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
                'totalItems'  => $count,
                'limit'       => $limit,
                'page'        => $page,
            ],
            'contentTemplate' => 'MauticLeadBundle:Field:list.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'route'         => $this->generateUrl('mautic_contactfield_index', ['page' => $page]),
                'mauticContent' => 'leadfield',
            ],
        ]);
    }

    /**
     * Generate's new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $field = new LeadField();
        /** @var FieldModel $model */
        $model = $this->getModel('lead.field');
        //set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $action    = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'new']);
        //get the user form factory
        $form = $model->createForm($field, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $request = $this->request->request->all();
                    if (isset($request['leadfield']['properties'])) {
                        $result = $model->setFieldProperties($field, $request['leadfield']['properties']);
                        if ($result !== true) {
                            //set the error
                            $form->get('properties')->addError(
                                new FormError(
                                    $this->get('translator')->trans($result, [], 'validators')
                                )
                            );
                            $valid = false;
                        }
                    }

                    if ($valid) {
                        $flashMessage = 'mautic.core.notice.created';
                        try {
                            //form is valid so process the data
                            $model->saveEntity($field);
                        } catch (DBALException $ee) {
                            $flashMessage = $ee->getMessage();
                        } catch (\Exception $e) {
                            $form['alias']->addError(
                                    new FormError(
                                        $this->get('translator')->trans('mautic.lead.field.failed', ['%error%' => $e->getMessage()], 'validators')
                                    )
                                );
                            $valid = false;
                        }
                        $this->addFlash(
                                $flashMessage,
                                [
                                    '%name%'      => $field->getLabel(),
                                    '%menu_link%' => 'mautic_contactfield_index',
                                    '%url%'       => $this->generateUrl(
                                        'mautic_contactfield_action',
                                        [
                                            'objectAction' => 'edit',
                                            'objectId'     => $field->getId(),
                                        ]
                                    ),
                                ]
                            );
                    }
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'contentTemplate' => 'MauticLeadBundle:Field:index',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_contactfield_index',
                            'mauticContent' => 'leadfield',
                        ],
                    ]
                );
            } elseif ($valid && !$cancelled) {
                return $this->editAction($field->getId(), true);
            } elseif (!$valid) {
                // some bug in Symfony prevents repopulating list options on errors
                $field   = $form->getData();
                $newForm = $model->createForm($field, $this->get('form.factory'), $action);
                $this->copyErrorsRecursively($form, $newForm);
                $form = $newForm;
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form->createView(),
                ],
                'contentTemplate' => 'MauticLeadBundle:Field:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contactfield_index',
                    'route'         => $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'new']),
                    'mauticContent' => 'leadfield',
                ],
            ]
        );
    }

    /**
     * Generate's edit form and processes post data.
     *
     * @param            $objectId
     * @param bool|false $ignorePost
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        /** @var FieldModel $model */
        $model = $this->getModel('lead.field');
        $field = $model->getEntity($objectId);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_contactfield_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Field:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'leadfield',
            ],
        ];
        //list not found
        if ($field === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.lead.field.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif ($model->isLocked($field)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $field, 'lead.field');
        }

        $action = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($field, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $request = $this->request->request->all();
                    if (isset($request['leadfield']['properties'])) {
                        $result = $model->setFieldProperties($field, $request['leadfield']['properties']);
                        if ($result !== true) {
                            //set the error
                            $form->get('properties')->addError(new FormError(
                                $this->get('translator')->trans($result, [], 'validators')
                            ));
                            $valid = false;
                        }
                    }

                    if ($valid) {
                        //form is valid so process the data
                        $model->saveEntity($field, $form->get('buttons')->get('save')->isClicked());

                        $this->addFlash('mautic.core.notice.updated', [
                            '%name%'      => $field->getLabel(),
                            '%menu_link%' => 'mautic_contactfield_index',
                            '%url%'       => $this->generateUrl('mautic_contactfield_action', [
                                'objectAction' => 'edit',
                                'objectId'     => $field->getId(),
                            ]),
                        ]);
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($field);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                            'viewParameters'  => ['objectId' => $field->getId()],
                            'contentTemplate' => 'MauticLeadBundle:Field:index',
                        ]
                    )
                );
            } elseif ($valid) {
                // Rebuild the form with new action so that apply doesn't keep creating a clone
                $action = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'edit', 'objectId' => $field->getId()]);
                $form   = $model->createForm($field, $this->get('form.factory'), $action);
            } else {
                // some bug in Symfony prevents repopulating list options on errors
                $field   = $form->getData();
                $newForm = $model->createForm($field, $this->get('form.factory'), $action);
                $this->copyErrorsRecursively($form, $newForm);
                $form = $newForm;
            }
        } else {
            //lock the entity
            $model->lockEntity($field);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => 'MauticLeadBundle:Field:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'route'         => $action,
                'mauticContent' => 'leadfield',
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param $objectId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('lead.field');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setIsPublished(false);
            $clone->setIsFixed(false);
            $this->get('mautic.helper.field.alias')->makeAliasUnique($clone);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Delete a field.
     *
     * @param   $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Field:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var FieldModel $model */
            $model = $this->getModel('lead.field');
            $field = $model->getEntity($objectId);

            if ($field === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.field.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($field)) {
                return $this->isLocked($postActionVars, $field, 'lead.field');
            } elseif ($field->isFixed()) {
                //cannot delete fixed fields
                return $this->accessDenied();
            }

            $segments = [];
            foreach ($model->getFieldSegments($field) as $segment) {
                $segments[] = sprintf('"%s" (%d)', $segment->getName(), $segment->getId());
            }

            if (count($segments)) {
                $flashMessage = [
                    'type'    => 'error',
                    'msg'     => 'mautic.core.notice.used.field',
                    'msgVars' => [
                        '%name%'     => $field->getLabel(),
                        '%id%'       => $objectId,
                        '%segments%' => implode(', ', $segments),
                    ],
                ];
            } else {
                $model->deleteEntity($field);
                $flashMessage = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $field->getLabel(),
                        '%id%'   => $objectId,
                    ],
                ];
            }

            $flashes[] = $flashMessage;
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        if (!$this->get('mautic.security')->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'MauticLeadBundle:Field:index',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var FieldModel $model */
            $model     = $this->getModel('lead.field');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.field.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif ($entity->isFixed()) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.field', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $filteredDeleteIds = $model->filterUsedFieldIds($deleteIds);
                $usedFieldIds      = array_diff($deleteIds, $filteredDeleteIds);
                $segments          = [];
                $usedFieldsNames   = [];

                if ($usedFieldIds) {
                    // Iterating through all used fileds to get segments they are used in
                    foreach ($usedFieldIds as $usedFieldId) {
                        $fieldEntity = $model->getEntity($usedFieldId);
                        foreach ($model->getFieldSegments($fieldEntity) as $segment) {
                            $segments[$segment->getId()] = sprintf('"%s" (%d)', $segment->getName(), $segment->getId());
                            $usedFieldsNames[]           = sprintf('"%s"', $fieldEntity->getName());
                        }
                    }
                }

                if ($filteredDeleteIds !== $deleteIds) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.core.notice.used.fields',
                        'msgVars' => [
                            '%segments%' => implode(', ', $segments),
                            '%fields%'   => implode(', ', array_unique($usedFieldsNames)),
                        ],
                    ];
                }

                if (count($filteredDeleteIds)) {
                    $entities = $model->deleteEntities($filteredDeleteIds);

                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.lead.field.notice.batch_deleted',
                        'msgVars' => [
                            '%count%' => count($entities),
                        ],
                    ];
                }
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
