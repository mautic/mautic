<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ProjectBundle\Entity\Project;
use Mautic\ProjectBundle\Form\Type\ProjectEntityType;
use Mautic\ProjectBundle\Model\ProjectModel;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ProjectController extends AbstractFormController
{
    public const ROUTE_INDEX     = 'mautic_project_index';
    private const ROUTE_ACTION   = 'mautic_project_action';
    private const LINK_ID_INDEX  = '#'.self::ROUTE_INDEX;
    private const TEMPLATE_INDEX = 'Mautic\ProjectBundle\Controller\ProjectController::indexAction';
    private const TEMPLATE_FORM  = '@MauticProject/Project/form.html.twig';

    public function indexAction(Request $request, ProjectModel $projectModel, CorePermissions $corePermissions, int $page = 1): Response
    {
        $session = $request->getSession();

        $permissions = $corePermissions->isGranted([
            ProjectPermissions::CAN_VIEW,
            ProjectPermissions::CAN_EDIT,
            ProjectPermissions::CAN_CREATE,
            ProjectPermissions::CAN_DELETE,
        ], 'RETURN_ARRAY');

        if (!$permissions[ProjectPermissions::CAN_VIEW]) {
            return $this->accessDenied();
        }

        $this->setListFilters();

        $limit = $session->get('mautic.project.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.projects.filter', ''));
        $session->set('mautic.projects.filter', $search);

        $orderBy    = $session->get('mautic.projects.orderby', 'p.dateModified');
        $orderByDir = $session->get('mautic.projects.orderbydir', 'DESC');
        $filter     = '';

        if ($search) {
            $filter = ['string' => $search];
        }

        $tmpl  = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';
        $items = $projectModel->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );

        $count = count($items);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.projects.page', $lastPage);
            $returnUrl = $this->generateUrl(self::ROUTE_INDEX, ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => self::TEMPLATE_INDEX,
                'passthroughVars' => [
                    'activeLink'    => self::LINK_ID_INDEX,
                    'mauticContent' => 'project',
                ],
            ]);
        }

        $session->set('mautic.project.page', $page);

        return $this->delegateView([
            'viewParameters'  => [
                'items'         => $items,
                'page'          => $page,
                'limit'         => $limit,
                'permissions'   => $permissions,
                'security'      => $corePermissions,
                'tmpl'          => $tmpl,
                'currentUser'   => $this->user,
                'searchValue'   => $search,
            ],
            'contentTemplate' => '@MauticProject/Project/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'route'         => $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]),
                'mauticContent' => 'projects',
            ],
        ]);
    }

    public function newAction(Request $request, ProjectModel $projectModel, FormFactoryInterface $formFactory, CorePermissions $corePermissions): Response
    {
        if (!$corePermissions->isGranted(ProjectPermissions::CAN_CREATE)) {
            return $this->accessDenied();
        }

        $project   = new Project();
        $page      = $request->getSession()->get('mautic.project.page', 1);
        $returnUrl = $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]);
        $action    = $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'new']);

        $form = $this->buildForm($project, $action, $formFactory);

        if ('POST' === $request->getMethod()) {
            $valid     = $this->isFormValid($form);
            $cancelled = $this->isFormCancelled($form);
            if (!$cancelled && $valid) {
                $projectModel->saveEntity($project);
                $this->addFlashMessage('mautic.core.notice.created', [
                    '%name%'      => $project->getName(),
                    '%menu_link%' => self::ROUTE_INDEX,
                    '%url%'       => $this->generateUrl(self::ROUTE_ACTION, [
                        'objectAction' => 'edit',
                        'objectId'     => $project->getId(),
                    ]),
                ]);
            }

            if ($cancelled || ($valid && $this->getFormButton($form, ['buttons', 'save'])->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => ['page' => $page],
                    'contentTemplate' => self::TEMPLATE_INDEX,
                    'passthroughVars' => [
                        'activeLink'    => self::LINK_ID_INDEX,
                        'mauticContent' => 'project',
                    ],
                ]);
            }

            if ($valid) {
                return $this->editAction($project->getId(), $request, $projectModel, $formFactory, $corePermissions, true);
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'   => $form->createView(),
                'entity' => $project,
            ],
            'contentTemplate' => self::TEMPLATE_FORM,
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'route'         => $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'new']),
                'mauticContent' => 'project',
            ],
        ]);
    }

    public function editAction(string|int $objectId, Request $request, ProjectModel $projectModel, FormFactoryInterface $formFactory, CorePermissions $corePermissions, bool $ignorePost = false): Response
    {
        if (!$corePermissions->isGranted(ProjectPermissions::CAN_EDIT)) {
            return $this->accessDenied();
        }

        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            /** @var ?Project $project */
            $project = $projectModel->getEntity($objectId);

            if (!$project instanceof Project) {
                throw new EntityNotFoundException(sprintf('Project with id %s not found.', $objectId));
            }

            $action = $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'edit', 'objectId' => $objectId]);
            $form   = $this->buildForm($project, $action, $formFactory);

            if (!$ignorePost && 'POST' === $request->getMethod()) {
                if ($this->isFormCancelled($form)) {
                    return $this->postActionRedirect($postActionVars);
                }

                if ($this->isFormValid($form)) {
                    $projectModel->saveEntity($project, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $project->getName(),
                        '%menu_link%' => self::ROUTE_INDEX,
                        '%url%'       => $this->generateUrl(self::ROUTE_ACTION, [
                            'objectAction' => 'edit',
                            'objectId'     => $project->getId(),
                        ]),
                    ]);

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        $contentTemplate                     = self::TEMPLATE_FORM;
                        $postActionVars['contentTemplate']   = $contentTemplate;
                        $postActionVars['forwardController'] = false;
                        $postActionVars['returnUrl']         = $this->generateUrl(self::ROUTE_ACTION, [
                            'objectAction' => 'edit',
                            'objectId'     => $project->getId(),
                        ]);

                        // Re-create the form once more with the fresh project and action.
                        // The alias was empty on redirect after cloning.
                        $editAction = $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'edit', 'objectId' => $project->getId()]);
                        $form       = $this->buildForm($project, $editAction, $formFactory);

                        $postActionVars['viewParameters'] = [
                            'objectAction' => 'edit',
                            'entity'       => $project,
                            'objectId'     => $project->getId(),
                            'form'         => $form->createView(),
                        ];

                        return $this->postActionRedirect($postActionVars);
                    }

                    return $this->viewAction($project->getId(), $request, $projectModel, $corePermissions);
                }
            }

            return $this->delegateView([
                'viewParameters' => [
                    'form'           => $form->createView(),
                    'entity'         => $project,
                    'currentProject' => $project->getId(),
                ],
                'contentTemplate' => self::TEMPLATE_FORM,
                'passthroughVars' => [
                    'activeLink'    => self::LINK_ID_INDEX,
                    'route'         => $action,
                    'mauticContent' => 'project',
                ],
            ]);
        } catch (AccessDeniedException) {
            return $this->accessDenied();
        } catch (EntityNotFoundException) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.project.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }
    }

    /**
     * @return array<mixed>
     */
    private function getPostActionVars(Request $request, string|int|null $objectId = null): array
    {
        if ($objectId) {
            $returnUrl       = $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'view', 'objectId' => $objectId]);
            $viewParameters  = ['objectAction' => 'view', 'objectId' => $objectId];
            $contentTemplate = 'Mautic\ProjectBundle\Controller\ProjectController::viewAction';
        } else {
            $page            = $request->getSession()->get('mautic.project.page', 1);
            $returnUrl       = $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]);
            $viewParameters  = ['page' => $page];
            $contentTemplate = self::TEMPLATE_INDEX;
        }

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'mauticContent' => 'project',
            ],
        ];
    }

    public function viewAction(string|int $objectId, Request $request, ProjectModel $projectModel, CorePermissions $corePermissions): Response
    {
        /** @var ?Project $project */
        $project = $projectModel->getEntity($objectId);

        $page = $request->getSession()->get('mautic.project.page', 1);
        if (null === $project) {
            $returnUrl = $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => self::TEMPLATE_INDEX,
                'passthroughVars' => [
                    'activeLink'    => self::LINK_ID_INDEX,
                    'mauticContent' => 'project',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.project.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        }

        if (!$corePermissions->isGranted(ProjectPermissions::CAN_VIEW)) {
            return $this->accessDenied();
        }

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl(self::ROUTE_ACTION, ['objectAction' => 'view', 'objectId' => $project->getId()]),
            'viewParameters' => [
                'project' => $project,
            ],
            'contentTemplate' => '@MauticProject/Project/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'mauticContent' => 'project',
            ],
        ]);
    }

    public function deleteAction(string $objectId, Request $request, ProjectModel $projectModel, CorePermissions $corePermissions): Response
    {
        $page      = $request->getSession()->get('mautic.project.page', 1);
        $returnUrl = $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => self::TEMPLATE_INDEX,
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'mauticContent' => 'project',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            /** @var ?Project $project */
            $project = $projectModel->getEntity($objectId);

            if (null === $project) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.project.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$corePermissions->isGranted(ProjectPermissions::CAN_DELETE)) {
                return $this->accessDenied();
            }

            $projectModel->deleteEntity($project);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $project->getName(),
                    '%id%'   => $objectId,
                ],
            ];
        }

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }

    public function batchDeleteAction(Request $request, ProjectModel $projectModel, CorePermissions $corePermissions): Response
    {
        $page      = $request->getSession()->get('mautic.project.page', 1);
        $returnUrl = $this->generateUrl(self::ROUTE_INDEX, ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => self::TEMPLATE_INDEX,
            'passthroughVars' => [
                'activeLink'    => self::LINK_ID_INDEX,
                'mauticContent' => 'project',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $ids       = json_decode($request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $projectModel->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.project.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$corePermissions->isGranted(ProjectPermissions::CAN_DELETE)) {
                    $flashes[] = $this->accessDenied(true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                try {
                    $entities = $projectModel->deleteEntities($deleteIds);
                } catch (ForeignKeyConstraintViolationException) {
                    $flashes[] = [
                        'type' => 'notice',
                        'msg'  => 'mautic.project.error.cannotbedeleted',
                    ];

                    return $this->postActionRedirect(
                        array_merge($postActionVars, ['flashes' => $flashes])
                    );
                }

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.project.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        }

        return $this->postActionRedirect(array_merge($postActionVars, ['flashes' => $flashes]));
    }

    /**
     * @return FormInterface<FormInterface>&FormInterface
     */
    private function buildForm(Project $project, string $action, FormFactoryInterface $formFactory): FormInterface
    {
        return $formFactory->create(ProjectEntityType::class, $project, ['action' => $action]);
    }
}
