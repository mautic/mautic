<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Exception\DeleteEntitiesDependencyException;
use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Helper\FieldAliasHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FieldController extends FormController
{
    /**
     * Generate's default list view.
     *
     * @param int $page
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function indexAction(Request $request, FieldModel $fieldModel, $page = 1)
    {
        // set some permissions
        $permissions = $this->security->isGranted(['lead:fields:view', 'lead:fields:full'], 'RETURN_ARRAY');

        $session = $request->getSession();

        if (!$permissions['lead:fields:view'] && !$permissions['lead:fields:full']) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $limit  = $session->get('mautic.leadfield.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $search = $request->get('search', $session->get('mautic.leadfield.filter', ''));
        $session->set('mautic.leadfield.filter', $search);

        // do some default filtering
        $orderBy    = $request->getSession()->get('mautic.leadfield.orderby', 'f.order');
        $orderByDir = $request->getSession()->get('mautic.leadfield.orderbydir', 'ASC');

        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $fields = $fieldModel->getEntities([
            'start'      => $start,
            'limit'      => $limit,
            'filter'     => [
                'string' => $search,
                'force'  => [
                    [
                        'column' => 'f.columnIsNotRemoved',
                        'value'  => false,
                        'expr'   => 'eq',
                    ],
                ],
            ],
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);
        $count = count($fields);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.leadfield.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_contactfield_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contactfield_index',
                    'mauticContent' => 'leadfield',
                ],
            ]);
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.leadfield.page', $page);

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

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
            'contentTemplate' => '@MauticLead/Field/list.html.twig',
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
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction(Request $request, ?LeadField $entity = null)
    {
        if (!$this->security->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        // retrieve the entity
        $field = $entity instanceof LeadField ? $entity : new LeadField();

        /** @var FieldModel $model */
        $model = $this->getModel('lead.field');
        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $action    = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'new']);
        // get the user form factory
        $form = $model->createForm($field, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $requestData = $request->request->all();
                    if (isset($requestData['leadfield']['properties'])) {
                        $result = $model->setFieldProperties($field, $requestData['leadfield']['properties']);
                        if (true !== $result) {
                            // set the error
                            $form->get('properties')->addError(
                                new FormError(
                                    $this->translator->trans($result, [], 'validators')
                                )
                            );
                            $valid = false;
                        }
                    }

                    if ($valid) {
                        $flashMessage = 'mautic.core.notice.created';
                        try {
                            // form is valid so process the data
                            $model->saveEntity($field);
                        } catch (\Doctrine\DBAL\Exception $ee) {
                            $flashMessage = $ee->getMessage();
                        } catch (AbortColumnCreateException) {
                            $flashMessage = $this->translator->trans('mautic.lead.field.pushed_to_background');
                        } catch (SchemaException $e) {
                            $flashMessage = $e->getMessage();
                            $form['alias']->addError(new FormError($e->getMessage()));
                            $valid = false;
                        } catch (\Exception $e) {
                            $form['alias']->addError(
                                new FormError(
                                    $this->translator->trans('mautic.lead.field.failed', ['%error%' => $e->getMessage()], 'validators')
                                )
                            );
                            $valid = false;
                        }
                        $this->addFlashMessage(
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

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
                        'passthroughVars' => [
                            'activeLink'    => '#mautic_contactfield_index',
                            'mauticContent' => 'leadfield',
                        ],
                    ]
                );
            } elseif ($valid) {
                return $this->editAction($request, $field->getId(), true);
            } elseif (!$valid) {
                // some bug in Symfony prevents repopulating list options on errors
                $field   = $form->getData();
                $newForm = $model->createForm($field, $this->formFactory, $action);
                $this->copyErrorsRecursively($form, $newForm);
                $form = $newForm;
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'      => $form->createView(),
                    'leadField' => $entity,
                ],
                'contentTemplate' => '@MauticLead/Field/form.html.twig',
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
     * @param bool|false $ignorePost
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, $objectId, $ignorePost = false)
    {
        if (!$this->security->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        /** @var FieldModel $model */
        $model = $this->getModel('lead.field');
        $field = $model->getEntity($objectId);

        // set the return URL
        $returnUrl = $this->generateUrl('mautic_contactfield_index');

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'leadfield',
            ],
        ];
        // list not found
        if (null === $field) {
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
            // deny access if the entity is locked
            return $this->isLocked($postActionVars, $field, 'lead.field');
        }

        $action = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($field, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $requestData = $request->request->all();
                    if (isset($requestData['leadfield']['properties'])) {
                        $result = $model->setFieldProperties($field, $requestData['leadfield']['properties']);
                        if (true !== $result) {
                            // set the error
                            $form->get('properties')->addError(new FormError(
                                $this->translator->trans($result, [], 'validators')
                            ));
                            $valid = false;
                        }
                    }

                    if ($valid) {
                        $flashMessage = 'mautic.core.notice.updated';

                        // form is valid so process the data
                        try {
                            $model->saveEntity($field, $this->getFormButton($form, ['buttons', 'save'])->isClicked());
                        } catch (AbortColumnUpdateException) {
                            $flashMessage = $this->translator->trans('mautic.lead.field.update_pushed_to_background');
                        } catch (SchemaException $e) {
                            $flashMessage = $e->getMessage();
                            $form['alias']->addError(new FormError($e->getMessage()));
                            $valid = false;
                        }

                        $this->addFlashMessage($flashMessage, [
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
                // unlock the entity
                $model->unlockEntity($field);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'viewParameters'  => ['objectId' => $field->getId()],
                        'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
                    ]
                    )
                );
            } elseif ($valid) {
                // Rebuild the form with new action so that apply doesn't keep creating a clone
                $action = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'edit', 'objectId' => $field->getId()]);
                $form   = $model->createForm($field, $this->formFactory, $action);
            } else {
                // some bug in Symfony prevents repopulating list options on errors
                $field   = $form->getData();
                $newForm = $model->createForm($field, $this->formFactory, $action);
                $this->copyErrorsRecursively($form, $newForm);
                $form = $newForm;
            }
        } else {
            // lock the entity
            $model->lockEntity($field);
        }

        return $this->delegateView([
            'viewParameters' => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticLead/Field/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'route'         => $action,
                'mauticContent' => 'leadfield',
            ],
        ]);
    }

    /**
     * Clone an entity.
     */
    public function cloneAction(Request $request, FieldAliasHelper $fieldAliasHelper, FieldModel $fieldModel, $objectId): RedirectResponse|Response
    {
        $entity = $fieldModel->getEntity($objectId);

        if (!$entity) {
            throw $this->createNotFoundException('Entity not found');
        }

        $clone = clone $entity;

        $fieldAliasHelper->makeAliasUnique($clone);

        $action    = $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'new']);
        $form      = $fieldModel->createForm($clone, $this->formFactory, $action);

        return $this->delegateView([
            'viewParameters' => [
                'form'      => $form->createView(),
                'leadField' => $clone,
            ],
            'contentTemplate' => '@MauticLead/Field/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'route'         => $this->generateUrl('mautic_contactfield_action', ['objectAction' => 'clone', 'objectId' => $objectId]),
                'mauticContent' => 'leadfield',
            ],
        ]);
    }

    /**
     * Delete a field.
     *
     * @return Response
     */
    public function deleteAction(Request $request, $objectId)
    {
        if (!$this->security->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var FieldModel $model */
            $model = $this->getModel('lead.field');
            $field = $model->getEntity($objectId);

            if (null === $field) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.field.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif ($model->isLocked($field)) {
                return $this->isLocked($postActionVars, $field, 'lead.field');
            } elseif ($field->isFixed()) {
                // cannot delete fixed fields
                return $this->accessDenied();
            }

            try {
                $model->deleteEntity($field);
                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.core.notice.deleted',
                    'msgVars' => [
                        '%name%' => $field->getLabel(),
                        '%id%'   => $objectId,
                    ],
                ];
            } catch (DeleteEntityDependencyException $exception) {
                foreach ($exception->getErrors() as $error) {
                    $flashes[] = [
                        'type' => 'error',
                        'msg'  => $error,
                    ];
                }
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     *
     * @return Response
     */
    public function batchDeleteAction(Request $request)
    {
        if (!$this->security->isGranted('lead:fields:full')) {
            return $this->accessDenied();
        }

        $returnUrl = $this->generateUrl('mautic_contactfield_index');
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'contentTemplate' => 'Mautic\LeadBundle\Controller\FieldController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_contactfield_index',
                'mauticContent' => 'lead',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var FieldModel $model */
            $model     = $this->getModel('lead.field');
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $flashes = array_merge($flashes,
                    $this->checkEntityForDeletion($objectId, $deleteIds, $postActionVars));
            }

            // Delete everything we are able to
            if ($deleteIds) {
                try {
                    $entities = $model->deleteEntities($deleteIds);
                    if ($entities) {
                        $flashes[] = [
                            'type'    => 'notice',
                            'msg'     => 'mautic.lead.field.notice.batch_deleted',
                            'msgVars' => [
                                '%count%' => count($entities),
                            ],
                        ];
                    }
                } catch (DeleteEntitiesDependencyException $e) {
                    $flashes = array_merge($flashes, $this->handleDeleteEntitiesDependencyException($e));
                }
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Check if the entity can be deleted and add it to the deleteIds array if it can.
     * Return an array of flash messages if the entity cannot be deleted.
     *
     * @param array<int>                                  $deleteIds
     * @param array<string, array<string, string>|string> $postActionVars
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkEntityForDeletion(int $objectId, array &$deleteIds, array $postActionVars): array
    {
        /** @var FieldModel $model */
        $model     = $this->getModel('lead.field');
        $entity    = $model->getEntity($objectId);
        $flashes   = [];
        if (null === $entity) {
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

        return $flashes;
    }

    /**
     * Handles dependency exceptions when deleting batch entities.
     *
     * @param DeleteEntitiesDependencyException $e the exception that was thrown during the delete process
     *
     * @return array<int, array<string, mixed>>
     */
    private function handleDeleteEntitiesDependencyException(DeleteEntitiesDependencyException $e): array
    {
        $flashes                = [];
        $deletedEntities        = $e->getDeletedEntities();
        $unableToDeleteEntities = $e->getUnableToDeleteEntities();

        if ($deletedEntities) {
            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.lead.field.notice.batch_deleted',
                'msgVars' => ['%count%' => count($deletedEntities)],
            ];
        }

        if ($unableToDeleteEntities) {
            $flashes[] = [
                'type'    => 'error',
                'msg'     => 'mautic.core.notice.used.fields',
                'msgVars' => [
                    '%fields%' => implode(', ', array_map(fn ($entity) => $entity->getName().' ('.$entity->getId().')', $unableToDeleteEntities)),
                ],
            ];
        }

        return $flashes;
    }
}
