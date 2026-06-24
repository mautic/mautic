<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\TagModel;
use MauticPlugin\MauticTagManagerBundle\Model\TagModel as TagManagerModel;
use MauticPlugin\MauticTagManagerBundle\Stats\TagDependencies;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TagController extends FormController
{
    private const PERMISSION_VIEW   = 'tagManager:tagManager:view';
    private const PERMISSION_EDIT   = 'tagManager:tagManager:edit';
    private const PERMISSION_DELETE = 'tagManager:tagManager:delete';
    private const PERMISSION_CREATE = 'tagManager:tagManager:create';

    private TagModel $leadTagModel;

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setLeadTagModel(TagModel $leadTagModel): void
    {
        $this->leadTagModel = $leadTagModel;
    }

    /**
     * Generate's default list view.
     *
     * @param int $page
     */
    public function indexAction(Request $request, $page = 1): Response
    {
        // Use overwritten tag model so overwritten repository can be fetched,
        // we need it to define table alias so we can define sort order.
        $model = $this->getModel('tagmanager.tag');
        \assert($model instanceof TagManagerModel);
        $session = $request->getSession();

        // set some permissions
        $permissions = $this->security->isGranted([
            self::PERMISSION_VIEW,
            self::PERMISSION_EDIT,
            self::PERMISSION_CREATE,
            self::PERMISSION_DELETE,
        ], 'RETURN_ARRAY');

        if (!$permissions[self::PERMISSION_VIEW]) {
            $this->throwAccessDenied();
        }

        $this->setListFilters();

        // set limits
        $limit = $session->get('mautic.tagmanager.limit', $this->coreParametersHelper->get('default_pagelimit'));
        $start = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $request->get('search', $session->get('mautic.tags.filter', ''));
        $session->set('mautic.tags.filter', $search);

        // do some default filtering
        $orderBy    = $session->get('mautic.tags.orderby', 'lt.tag');
        $orderByDir = $session->get('mautic.tags.orderbydir', 'ASC');

        $filter = !empty($search) ? ['string' => $search] : '';

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        $items = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]
        );
        \assert($items instanceof Paginator);

        $count = count($items);

        if ($count && $count < ($start + 1)) {
            // the number of entities are now less then the current page so redirect to the last page
            if (1 === $count) {
                $lastPage = 1;
            } else {
                $lastPage = (ceil($count / $limit)) ?: 1;
            }
            $session->set('mautic.tags.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'      => $returnUrl,
                'viewParameters' => [
                    'page' => $lastPage,
                    'tmpl' => $tmpl,
                ],
                'contentTemplate' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_tagmanager_index',
                    'mauticContent' => 'tagmanager',
                ],
            ]);
        }

        // set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.tagmanager.page', $page);

        $tagIds    = array_map(fn (Tag $tag) => $tag->getId(), iterator_to_array($items->getIterator()));
        $tagsCount = (!empty($tagIds)) ? $model->getRepository()->countByLeads($tagIds) : [];

        $parameters = [
            'items'       => $items,
            'tagsCount'   => $tagsCount,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->security,
            'tmpl'        => $tmpl,
            'currentUser' => $this->user,
            'searchValue' => $search,
        ];

        return $this->delegateView([
            'viewParameters'  => $parameters,
            'contentTemplate' => '@MauticTagManager/Tag/list.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'route'         => $this->generateUrl('mautic_tagmanager_index', ['page' => $page]),
                'mauticContent' => 'tags',
            ],
        ]);
    }

    /**
     * Generate's new form and processes post data.
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function newAction(Request $request, TagDependencies $tagDependencies)
    {
        if (!$this->security->isGranted(self::PERMISSION_CREATE)) {
            $this->throwAccessDenied();
        }

        // retrieve the entity
        $tag   = new \MauticPlugin\MauticTagManagerBundle\Entity\Tag();
        $model = $this->getModel('tagmanager.tag');
        \assert($model instanceof TagManagerModel);
        // set the page we came from
        $page = $request->getSession()->get('mautic.tagmanager.page', 1);
        // set the return URL for post actions
        $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);
        $action    = $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'new']);

        // get the user form factory
        $form = $model->createForm($tag, $this->formFactory, $action);

        $response = $this->handleNewActionPost($request, $tagDependencies, $tag, $model, $form, $returnUrl, $page);
        if (null === $response) {
            $response = $this->delegateView([
                'viewParameters' => [
                    'form'   => $form->createView(),
                    'entity' => $tag,
                ],
                'contentTemplate' => '@MauticTagManager/Tag/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_tagmanager_index',
                    'route'         => $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'new']),
                    'mauticContent' => 'tagmanager',
                ],
            ]);
        }

        return $response;
    }

    private function handleNewActionPost(Request $request, TagDependencies $tagDependencies, \MauticPlugin\MauticTagManagerBundle\Entity\Tag $tag, TagManagerModel $model, FormInterface $form, string $returnUrl, int $page): ?Response
    {
        if (Request::METHOD_POST !== $request->getMethod()) {
            return null;
        }

        $valid = false;
        if (!$cancelled = $this->isFormCancelled($form)) {
            if ($valid = $this->isFormValid($form)) {
                // form is valid so process the data
                $found = $model->getRepository()->countOccurrences($tag->getTag());
                if (0 !== $found) {
                    $valid = false;
                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $tag->getTag(),
                        '%menu_link%' => 'mautic_tagmanager_index',
                        '%url%'       => $this->generateUrl('mautic_tagmanager_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $tag->getId(),
                        ]),
                    ]);
                } else {
                    $model->saveEntity($tag);

                    $this->addFlashMessage('mautic.core.notice.created', [
                        '%name%'      => $tag->getTag(),
                        '%menu_link%' => 'mautic_tagmanager_index',
                        '%url%'       => $this->generateUrl('mautic_tagmanager_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $tag->getId(),
                        ]),
                    ]);
                }
            }
        }

        /** @var SubmitButton $saveSubmitButton */
        $saveSubmitButton = $form->get('buttons')->get('save');

        if ($cancelled || ($valid && $saveSubmitButton->isClicked())) {
            $response = $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_tagmanager_index',
                    'mauticContent' => 'tagmanager',
                ],
            ]);
        } elseif ($valid) {
            $response = $this->editAction($request, $tagDependencies, $tag->getId(), true);
        } else {
            $response = null;
        }

        return $response;
    }

    /**
     * Generate's edit form and processes post data.
     */
    public function editAction(Request $request, TagDependencies $tagDependencies, int $objectId, bool $ignorePost = false): Response
    {
        if (!$this->security->isGranted(self::PERMISSION_EDIT)) {
            $this->throwAccessDenied();
        }

        $postActionVars = $this->getPostActionVars($request, $objectId);

        try {
            return $this->createTagModifyResponse(
                $request,
                $this->getTag($objectId),
                $tagDependencies,
                $postActionVars,
                $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                $ignorePost
            );
        } catch (EntityNotFoundException) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.tagmanager.tag.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        }
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function createTagModifyResponse(Request $request, Tag $tag, TagDependencies $tagDependencies, array $postActionVars, string $action, bool $ignorePost): Response
    {
        /** @var TagModel $tagModel */
        $tagModel = $this->getModel('tagmanager.tag');

        /** @var FormInterface<FormInterface<Tag>> $form */
        $form = $tagModel->createForm($tag, $this->formFactory, $action);

        // /Check for a submitted form and process it
        if (!$ignorePost && 'POST' === $request->getMethod()) {
            $response = $this->handleEditFormPost($request, $tag, $tagDependencies, $tagModel, $form, $postActionVars);
            if (null !== $response) {
                return $response;
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'       => $form->createView(),
                'entity'     => $tag,
                'currentTag' => $tag->getId(),
            ],
            'contentTemplate' => '@MauticTagManager/Tag/form.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'route'         => $action,
                'mauticContent' => 'tagmanager',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function handleEditFormPost(Request $request, Tag $tag, TagDependencies $tagDependencies, TagModel $tagModel, FormInterface $form, array $postActionVars): ?Response
    {
        $response = null;

        if (!$this->isFormCancelled($form)) {
            if ($this->isFormValid($form)) {
                $isUnique = $this->isTagUnique($tag, $tagModel);

                if (!$isUnique) {
                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $tag->getTag(),
                        '%menu_link%' => 'mautic_tagmanager_index',
                        '%url%'       => $this->generateUrl('mautic_tagmanager_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $tag->getId(),
                        ]),
                    ]);
                } else {
                    // form is valid so process the data
                    $tagModel->saveEntity($tag, $this->getFormButton($form, ['buttons', 'save'])->isClicked());

                    $this->addFlashMessage('mautic.core.notice.updated', [
                        '%name%'      => $tag->getTag(),
                        '%menu_link%' => 'mautic_tagmanager_index',
                        '%url%'       => $this->generateUrl('mautic_tagmanager_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $tag->getId(),
                        ]),
                    ]);
                }

                if ($this->getFormButton($form, ['buttons', 'apply'])->isClicked()) {
                    $contentTemplate                     = '@MauticTagManager/Tag/form.html.twig';
                    $postActionVars['contentTemplate']   = $contentTemplate;
                    $postActionVars['forwardController'] = false;
                    $postActionVars['returnUrl']         = $this->generateUrl('mautic_tagmanager_action', [
                        'objectAction' => 'edit',
                        'objectId'     => $tag->getId(),
                    ]);

                    // Re-create the form once more with the fresh tag and action.
                    // The alias was empty on redirect after cloning.
                    $editAction = $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'edit', 'objectId' => $tag->getId()]);
                    $form       = $tagModel->createForm($tag, $this->formFactory, $editAction);

                    $postActionVars['viewParameters'] = [
                        'objectAction' => 'edit',
                        'entity'       => $tag,
                        'objectId'     => $tag->getId(),
                        'form'         => $this->getFormView($form, 'edit'),
                    ];

                    $response = $this->postActionRedirect($postActionVars);
                } else {
                    $response = $this->viewAction($request, $tagDependencies, $tag->getId());
                }
            }
        } else {
            $response = $this->postActionRedirect($postActionVars);
        }

        return $response;
    }

    private function isTagUnique(Tag $tag, TagModel $tagModel): bool
    {
        $existingTags = $tagModel->getRepository()->getTagsByName([$tag->getTag()]);
        foreach ($existingTags as $existingTag) {
            if ($existingTag->getId() != $tag->getId()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return tag if exists and user has access.
     *
     * @param int $tagId
     *
     * @return Tag
     *
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    private function getTag($tagId)
    {
        /** @var Tag|null $tag */
        $tag = $this->getModel('lead.tag')->getEntity($tagId);

        // Check if exists
        if (!$tag) {
            throw new EntityNotFoundException(sprintf('Tag with id %d not found.', $tagId));
        }

        return $tag;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPostActionVars(Request $request, ?int $objectId = null): array
    {
        // set the return URL
        if ($objectId) {
            $returnUrl       = $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'view', 'objectId'=> $objectId]);
            $viewParameters  = ['objectAction' => 'view', 'objectId'=> $objectId];
            $contentTemplate = 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::viewAction';
        } else {
            // set the page we came from
            $page            = $request->getSession()->get('mautic.tagmanager.page', 1);
            $returnUrl       = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);
            $viewParameters  = ['page' => $page];
            $contentTemplate = 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction';
        }

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'mauticContent' => 'tagmanager',
            ],
        ];
    }

    /**
     * Loads a specific form into the detailed panel.
     */
    public function viewAction(Request $request, TagDependencies $tagDependencies, int $objectId): Response
    {
        /** @var TagModel $model */
        $model    = $this->getModel('lead.tag');
        $security = $this->security;

        $tag = $model->getEntity($objectId);

        // set the page we came from
        $page = $request->getSession()->get('mautic.tagmanager.page', 1);
        if (null === $tag) {
            // set the return URL
            $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_tagmanager_index',
                    'mauticContent' => 'tagmanager',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.tagmanager.tag.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$this->security->isGranted(self::PERMISSION_VIEW)) {
            $this->throwAccessDenied();
        }

        return $this->delegateView([
            'returnUrl'      => $this->generateUrl('mautic_tagmanager_action', ['objectAction' => 'view', 'objectId' => $tag->getId()]),
            'viewParameters' => [
                'tag'        => $tag,
                'security'   => $security,
                'usageStats' => $tagDependencies->getChannelsIds($tag),
            ],
            'contentTemplate' => '@MauticTagManager/Tag/details.html.twig',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'mauticContent' => 'tagmanager',
            ],
        ]);
    }

    /**
     * Merge two tags together.
     */
    public function mergeAction(Request $request, int $objectId): Response
    {
        $permissions = $this->security->isGranted(
            [
                self::PERMISSION_VIEW,
                self::PERMISSION_EDIT,
                self::PERMISSION_DELETE,
            ],
            'RETURN_ARRAY'
        );

        if (!$permissions[self::PERMISSION_VIEW]) {
            $this->throwAccessDenied();
        } else {
            $secondaryTag = $this->leadTagModel->getEntity($objectId);

            if (null === $secondaryTag) {
                $response = $this->handleTagNotFound($objectId);
            } else {
                $postActionVars = $this->getMergePostActionVars($request);
                $action         = $this->generateUrl('mautic_tagmanager_action', [
                    'objectAction' => 'merge',
                    'objectId'     => $secondaryTag->getId(),
                ]);

                $form = $this->formFactory->create(
                    \MauticPlugin\MauticTagManagerBundle\Form\Type\TagMergeType::class,
                    [],
                    [
                        'action'      => $action,
                        'exclude_ids' => [$secondaryTag->getId()],
                    ]
                );

                $response = 'POST' === $request->getMethod()
                    ? $this->handleMergePostRequest($form, $secondaryTag, $permissions, $postActionVars)
                    : $this->renderMergeForm($request, $action, $form, $secondaryTag);
            }
        }

        return $response;
    }

    private function handleTagNotFound(int $objectId): Response
    {
        $postActionVars = $this->getMergePostActionVars($this->getCurrentRequest());

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.tagmanager.tag.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ]
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getMergePostActionVars(Request $request): array
    {
        $page      = $request->getSession()->get('mautic.tagmanager.page', 1);
        $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);

        return [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPlugin\\MauticTagManagerBundle\\Controller\\TagController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'mauticContent' => 'tagmanager',
            ],
        ];
    }

    /**
     * @param array<string, bool>  $permissions
     * @param array<string, mixed> $postActionVars
     */
    private function handleMergePostRequest(FormInterface $form, Tag $secondaryTag, array $permissions, array $postActionVars): Response
    {
        if ($this->isFormCancelled($form) || !$this->isFormValid($form)) {
            $response = $this->handleFormCancellation($secondaryTag);
        } else {
            $data = $form->getData();
            /** @var Tag|null $primaryTag */
            $primaryTag = $data['tag_to_merge'];

            if (null === $primaryTag) {
                $response = $this->handlePrimaryTagNotFound($postActionVars);
            } elseif (!$permissions[self::PERMISSION_EDIT] || !$permissions[self::PERMISSION_DELETE]) {
                $this->throwAccessDenied();
            } else {
                $response = $this->performTagMerge($primaryTag, $secondaryTag);
            }
        }

        return $response;
    }

    private function handleFormCancellation(Tag $secondaryTag): Response
    {
        $viewParameters = [
            'objectId'     => $secondaryTag->getId(),
            'objectAction' => 'view',
        ];

        return $this->postActionRedirect([
            'returnUrl'       => $this->generateUrl('mautic_tagmanager_action', $viewParameters),
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticPlugin\\MauticTagManagerBundle\\Controller\\TagController::viewAction',
            'passthroughVars' => [
                'closeModal' => 1,
            ],
            'flashes' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $postActionVars
     */
    private function handlePrimaryTagNotFound(array $postActionVars): Response
    {
        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.tagmanager.tag.error.notfound',
                            'msgVars' => ['%id%' => 'unknown'],
                        ],
                    ],
                ]
            )
        );
    }

    private function performTagMerge(Tag $primaryTag, Tag $secondaryTag): Response
    {
        $this->leadTagModel->tagMerge($primaryTag, $secondaryTag);

        $viewParameters = [
            'objectId'     => $primaryTag->getId(),
            'objectAction' => 'view',
        ];

        $flashes = [
            [
                'type'    => 'notice',
                'msg'     => 'mautic.tagmanager.tag.notice.merge_success',
                'msgVars' => [
                    '%primary%'   => $primaryTag->getTag(),
                    '%secondary%' => $secondaryTag->getTag(),
                ],
            ],
        ];

        return $this->postActionRedirect([
            'returnUrl'       => $this->generateUrl('mautic_tagmanager_action', $viewParameters),
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticPlugin\\MauticTagManagerBundle\\Controller\\TagController::viewAction',
            'passthroughVars' => [
                'closeModal' => 1,
            ],
            'flashes' => $flashes,
        ]);
    }

    private function renderMergeForm(Request $request, string $action, FormInterface $form, Tag $secondaryTag): Response
    {
        $tmpl = $request->get('tmpl', 'index');

        return $this->delegateView([
            'viewParameters' => [
                'tmpl'         => $tmpl,
                'action'       => $action,
                'form'         => $form->createView(),
                'currentRoute' => $this->generateUrl(
                    'mautic_tagmanager_action',
                    [
                        'objectAction' => 'merge',
                        'objectId'     => $secondaryTag->getId(),
                    ]
                ),
            ],
            'contentTemplate' => '@MauticTagManager/Tag/merge.html.twig',
            'passthroughVars' => [
                'route'  => false,
                'target' => ('update' == $tmpl) ? '.tag-merge-options' : null,
            ],
        ]);
    }

    /**
     * Deletes a tags.
     */
    public function deleteAction(Request $request, $objectId): Response
    {
        /** @var TagModel $model */
        $model     = $this->getModel('lead.tag');
        $page      = $request->getSession()->get('mautic.tagmanager.page', 1);
        $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'mauticContent' => 'tagmanager',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $tag = $model->getEntity($objectId);

            if (null === $tag) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.tagmanager.tag.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->security->isGranted(self::PERMISSION_DELETE)) {
                $this->throwAccessDenied();
            }

            $model->deleteEntity($tag);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $tag->getTag(),
                    '%id%'   => $objectId,
                ],
            ];
        }

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Deletes a group of entities.
     */
    public function batchDeleteAction(Request $request): Response
    {
        $model     = $this->leadTagModel;
        $page      = $request->getSession()->get('mautic.tagmanager.page', 1);
        $returnUrl = $this->generateUrl('mautic_tagmanager_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPlugin\MauticTagManagerBundle\Controller\TagController::indexAction',
            'passthroughVars' => [
                'activeLink'    => '#mautic_tagmanager_index',
                'mauticContent' => 'tagmanager',
            ],
        ];

        if ('POST' === $request->getMethod()) {
            $ids             = json_decode($request->query->get('ids', '{}'));
            $deleteIds       = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if (null === $entity) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.tagmanager.tag.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->security->isGranted(self::PERMISSION_DELETE)) {
                    $flashes[] = $this->getAccessDeniedFlash();
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                try {
                    $entities = $model->deleteEntities($deleteIds);
                } catch (ForeignKeyConstraintViolationException) {
                    $flashes[] = [
                        'type'    => 'notice',
                        'msg'     => 'mautic.tagmanager.tag.error.cannotbedeleted',
                    ];

                    return $this->postActionRedirect(
                        array_merge($postActionVars, [
                            'flashes' => $flashes,
                        ])
                    );
                }

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.tagmanager.tag.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } // else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }
}
