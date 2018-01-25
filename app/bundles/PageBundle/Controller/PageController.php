<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Doctrine\ORM\Query\Expr;
use Mautic\CoreBundle\Controller\BuilderControllerTrait;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Controller\FormErrorMessagesTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PageController.
 */
class PageController extends FormController
{
    use BuilderControllerTrait;
    use FormErrorMessagesTrait;

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        $model = $this->getModel('page.page');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted([
            'page:pages:viewown',
            'page:pages:viewother',
            'page:pages:create',
            'page:pages:editown',
            'page:pages:editother',
            'page:pages:deleteown',
            'page:pages:deleteother',
            'page:pages:publishown',
            'page:pages:publishother',
            'page:preference_center:viewown',
            'page:preference_center:viewother',
        ], 'RETURN_ARRAY');

        if (!$permissions['page:pages:viewown'] && !$permissions['page:pages:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.page.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.page.filter', ''));
        $this->get('session')->set('mautic.page.filter', $search);

        $filter = ['string' => $search, 'force' => []];

        if (!$permissions['page:pages:viewother']) {
            $filter['force'][] = ['column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()];
        }

        if (!$permissions['page:preference_center:viewown'] && !$permissions['page:preference_center:viewother']) {
            $filter['where'][] = [
                'expr' => 'orX',
                'val'  => [
                    ['column' => 'p.isPreferenceCenter', 'expr' => 'isNull'],
                    ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 0],
                ],
            ];
        } elseif (!$permissions['page:preference_center:viewother']) {
            $filter['where'][] = [
                'expr' => 'orX',
                'val'  => [
                        [
                            'expr' => 'orX',
                            'val'  => [
                                ['column' => 'p.isPreferenceCenter', 'expr' => 'isNull'],
                                ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 0],
                            ],
                        ],
                        [
                            'expr' => 'andX',
                            'val'  => [
                                ['column' => 'p.isPreferenceCenter', 'expr' => 'eq', 'value' => 1],
                                ['column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->user->getId()],
                            ],
                        ],
                    ],
                ];
        }

        $translator = $this->get('translator');

        //do not list variants in the main list
        $filter['force'][] = ['column' => 'p.variantParent', 'expr' => 'isNull'];

        $langSearchCommand = $translator->trans('mautic.core.searchcommand.lang');
        if (strpos($search, "{$langSearchCommand}:") === false) {
            $filter['force'][] = ['column' => 'p.translationParent', 'expr' => 'isNull'];
        }

        $orderBy    = $this->get('session')->get('mautic.page.orderby', 'p.title');
        $orderByDir = $this->get('session')->get('mautic.page.orderbydir', 'DESC');

        $pages = $model->getEntities(
            [
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir,
            ]);

        $count = count($pages);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->get('session')->set('mautic.page.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $lastPage]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $lastPage],
                'contentTemplate' => 'MauticPageBundle:Page:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page',
                ],
            ]);
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.page.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->getModel('page.page')->getLookupResults('category', '', 0);

        return $this->delegateView([
            'viewParameters' => [
                'searchValue' => $search,
                'items'       => $pages,
                'categories'  => $categories,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->get('mautic.security'),
            ],
            'contentTemplate' => 'MauticPageBundle:Page:list.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Loads a specific form into the detailed panel.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page.page');
        //set some permissions
        $security   = $this->get('mautic.security');
        $activePage = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->get('session')->get('mautic.page.page', 1);

        if ($activePage === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);

            return $this->postActionRedirect([
                'returnUrl'       => $returnUrl,
                'viewParameters'  => ['page' => $page],
                'contentTemplate' => 'MauticPageBundle:Page:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page',
                ],
                'flashes' => [
                    [
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ],
                ],
            ]);
        } elseif (!$security->hasEntityAccess(
                'page:pages:viewown', 'page:pages:viewother', $activePage->getCreatedBy()
            ) ||
            ($activePage->getIsPreferenceCenter() &&
                !$security->hasEntityAccess(
                    'page:preference_center:viewown', 'page:preference_center:viewother', $activePage->getCreatedBy()
                ))) {
            return $this->accessDenied();
        }

        //get A/B test information
        list($parent, $children) = $activePage->getVariants();
        $properties              = [];
        $variantError            = false;
        $weight                  = 0;
        if (count($children)) {
            foreach ($children as $c) {
                $variantSettings = $c->getVariantSettings();

                if (is_array($variantSettings) && isset($variantSettings['winnerCriteria'])) {
                    if ($c->isPublished()) {
                        if (!isset($lastCriteria)) {
                            $lastCriteria = $variantSettings['winnerCriteria'];
                        }

                        //make sure all the variants are configured with the same criteria
                        if ($lastCriteria != $variantSettings['winnerCriteria']) {
                            $variantError = true;
                        }

                        $weight += $variantSettings['weight'];
                    }
                } else {
                    $variantSettings['winnerCriteria'] = '';
                    $variantSettings['weight']         = 0;
                }

                $properties[$c->getId()] = $variantSettings;
            }

            $properties[$parent->getId()]['weight']         = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        $abTestResults = [];
        $criteria      = $model->getBuilderComponents($activePage, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            //there is a criteria to compare the pages against so let's shoot the page over to the criteria function to do its thing
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = [
                    'factory'    => $this->factory,
                    'page'       => $activePage,
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties,
                ];

                //execute the callback
                if (is_callable($testSettings['callback'])) {
                    if (is_array($testSettings['callback'])) {
                        $reflection = new \ReflectionMethod($testSettings['callback'][0], $testSettings['callback'][1]);
                    } elseif (strpos($testSettings['callback'], '::') !== false) {
                        $parts      = explode('::', $testSettings['callback']);
                        $reflection = new \ReflectionMethod($parts[0], $parts[1]);
                    } else {
                        $reflection = new \ReflectionMethod(null, $testSettings['callback']);
                    }

                    $pass = [];
                    foreach ($reflection->getParameters() as $param) {
                        if (isset($args[$param->getName()])) {
                            $pass[] = $args[$param->getName()];
                        } else {
                            $pass[] = null;
                        }
                    }
                    $abTestResults = $reflection->invokeArgs($this, $pass);
                }
            }
        }

        // Init the date range filter form
        $dateRangeValues = $this->request->get('daterange', []);
        $action          = $this->generateUrl('mautic_page_action', ['objectAction' => 'view', 'objectId' => $objectId]);
        $dateRangeForm   = $this->get('form.factory')->create('daterange', $dateRangeValues, ['action' => $action]);

        // Audit Log
        $logs = $this->getModel('core.auditLog')->getLogForObject('page', $activePage->getId(), $activePage->getDateAdded());

        $pageviews = $model->getHitsLineChartData(
            null,
            new \DateTime($dateRangeForm->get('date_from')->getData()),
            new \DateTime($dateRangeForm->get('date_to')->getData()),
            null,
            ['page_id' => $activePage->getId(), 'flag' => 'total_and_unique']
        );

        //get related translations
        list($translationParent, $translationChildren) = $activePage->getTranslations();

        return $this->delegateView([
            'returnUrl' => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'view',
                    'objectId'     => $activePage->getId(), ]
            ),
            'viewParameters' => [
                'activePage' => $activePage,
                'variants'   => [
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties,
                    'criteria'   => $criteria['criteria'],
                ],
                'translations' => [
                    'parent'   => $translationParent,
                    'children' => $translationChildren,
                ],
                'permissions' => $security->isGranted([
                    'page:pages:viewown',
                    'page:pages:viewother',
                    'page:pages:create',
                    'page:pages:editown',
                    'page:pages:editother',
                    'page:pages:deleteown',
                    'page:pages:deleteother',
                    'page:pages:publishown',
                    'page:pages:publishother',
                    'page:preference_center:viewown',
                    'page:preference_center:viewother',
                ], 'RETURN_ARRAY'),
                'stats' => [
                    'pageviews' => $pageviews,
                    'hits'      => [
                        'total'  => $activePage->getHits(),
                        'unique' => $activePage->getUniqueHits(),
                    ],
                ],
                'abTestResults' => $abTestResults,
                'security'      => $security,
                'pageUrl'       => $model->generateUrl($activePage, true),
                'previewUrl'    => $this->generateUrl('mautic_page_preview', ['id' => $objectId], true),
                'logs'          => $logs,
                'dateRangeForm' => $dateRangeForm->createView(),
            ],
            'contentTemplate' => 'MauticPageBundle:Page:details.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
            ],
        ]);
    }

    /**
     * Generates new form and processes post data.
     *
     * @param \Mautic\PageBundle\Entity\Page|null $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction($entity = null)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page.page');

        if (!($entity instanceof Page)) {
            /** @var \Mautic\PageBundle\Entity\Page $entity */
            $entity = $model->getEntity();
        }

        $method  = $this->request->getMethod();
        $session = $this->get('session');
        if (!$this->get('mautic.security')->isGranted('page:pages:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.page.page', 1);
        $action = $this->generateUrl('mautic_page_action', ['objectAction' => 'new']);

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ];
                        $returnUrl = $this->generateUrl('mautic_page_action', $viewParameters);
                        $template  = 'MauticPageBundle:Page:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters = ['page' => $page];
                $returnUrl      = $this->generateUrl('mautic_page_index', $viewParameters);
                $template       = 'MauticPageBundle:Page:index';
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$entity->getSessionId().'.content');
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect([
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'page',
                    ],
                ]);
            }
        }

        $slotTypes   = $model->getBuilderComponents($entity, 'slotTypes');
        $sections    = $model->getBuilderComponents($entity, 'sections');
        $sectionForm = $this->get('form.factory')->create('builder_section');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
            [
                'page:preference_center:editown',
                'page:preference_center:editother',
            ],
            'RETURN_ARRAY'
        );

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $this->setFormTheme($form, 'MauticPageBundle:Page:form.html.php', 'MauticPageBundle:FormTheme\Page'),
                'isVariant'     => $entity->isVariant(true),
                'tokens'        => $model->getBuilderComponents($entity, 'tokens'),
                'activePage'    => $entity,
                'themes'        => $this->factory->getInstalledThemes('page', true),
                'slots'         => $this->buildSlotForms($slotTypes),
                'sections'      => $this->buildSlotForms($sections),
                'builderAssets' => trim(preg_replace('/\s+/', ' ', $this->getAssetsForBuilder())), // strip new lines
                'sectionForm'   => $sectionForm->createView(),
                'permissions'   => $permissions,
            ],
            'contentTemplate' => 'MauticPageBundle:Page:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'new',
                ]),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model    = $this->getModel('page.page');
        $security = $this->get('mautic.security');
        $entity   = $model->getEntity($objectId);
        $session  = $this->get('session');
        $page     = $this->get('session')->get('mautic.page.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, [
                    'flashes' => [
                        [
                            'type'    => 'error',
                            'msg'     => 'mautic.page.error.notfound',
                            'msgVars' => ['%id%' => $objectId],
                        ],
                    ],
                ])
            );
        } elseif (!$security->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
        ) ||
            ($entity->getIsPreferenceCenter() && !$security->hasEntityAccess(
                    'page:preference_center:viewown', 'page:preference_center:viewother', $entity->getCreatedBy()
                ))) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'page.page');
        }

        //Create the form
        $action = $this->generateUrl('mautic_page_action', ['objectAction' => 'edit', 'objectId' => $objectId]);
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $content = $entity->getCustomHtml();
                    $entity->setCustomHtml($content);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash('mautic.core.notice.updated', [
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]),
                    ]);
                }
            } else {
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$objectId.'.content');
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = [
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                ];

                return $this->postActionRedirect(
                    array_merge($postActionVars, [
                        'returnUrl'       => $this->generateUrl('mautic_page_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'MauticPageBundle:Page:view',
                    ])
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);

            //clear any modified content
            $session->remove('mautic.pagebuilder.'.$objectId.'.content');

            //set the lookup values
            $parent = $entity->getTranslationParent();
            if ($parent && isset($form['translationParent_lookup'])) {
                $form->get('translationParent_lookup')->setData($parent->getTitle());
            }

            // Set to view content
            $template = $entity->getTemplate();
            if (empty($template)) {
                $content = $entity->getCustomHtml();
                $form['customHtml']->setData($content);
            }
        }

        $slotTypes   = $model->getBuilderComponents($entity, 'slotTypes');
        $sections    = $model->getBuilderComponents($entity, 'sections');
        $sectionForm = $this->get('form.factory')->create('builder_section');

        return $this->delegateView([
            'viewParameters' => [
                'form'          => $this->setFormTheme($form, 'MauticPageBundle:Page:form.html.php', 'MauticPageBundle:FormTheme\Page'),
                'isVariant'     => $entity->isVariant(true),
                'tokens'        => $model->getBuilderComponents($entity, 'tokens'),
                'activePage'    => $entity,
                'themes'        => $this->factory->getInstalledThemes('page', true),
                'slots'         => $this->buildSlotForms($slotTypes),
                'sections'      => $this->buildSlotForms($sections),
                'builderAssets' => trim(preg_replace('/\s+/', ' ', $this->getAssetsForBuilder())), // strip new lines
                'sectionForm'   => $sectionForm->createView(),
                'permissions'   => $security->isGranted(
                    [
                        'page:preference_center:editown',
                        'page:preference_center:editother',
                    ],
                    'RETURN_ARRAY'
                ),
                'security' => $security,
            ],
            'contentTemplate' => 'MauticPageBundle:Page:form.html.php',
            'passthroughVars' => [
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', [
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId(),
                ]),
                'validationError' => $this->getFormErrorForBuilder($form),
            ],
        ]);
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function cloneAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model  = $this->getModel('page.page');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('page:pages:create') ||
                !$this->get('mautic.security')->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
            $entity->setHits(0);
            $entity->setUniqueHits(0);
            $entity->setRevision(0);
            $entity->setVariantStartDate(null);
            $entity->setVariantHits(0);
            $entity->setIsPublished(false);

            $session     = $this->get('session');
            $contentName = 'mautic.pagebuilder.'.$entity->getSessionId().'.content';

            $session->set($contentName, $entity->getCustomHtml());
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity.
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model  = $this->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'page:pages:deleteown',
                'page:pages:deleteother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->deleteEntity($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId,
                ],
            ];
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
        $page      = $this->get('session')->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model     = $this->getModel('page');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = [];

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = [
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => ['%id%' => $objectId],
                    ];
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
                    'page:pages:deleteown', 'page:pages:deleteother', $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'page', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = [
                    'type'    => 'notice',
                    'msg'     => 'mautic.page.notice.batch_deleted',
                    'msgVars' => [
                        '%count%' => count($entities),
                    ],
                ];
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * Activate the builder.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function builderAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->getModel('page.page');

        //permission check
        if (strpos($objectId, 'new') !== false) {
            $isNew = true;
            if (!$this->get('mautic.security')->isGranted('page:pages:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew  = false;
            $entity = $model->getEntity($objectId);
            if ($entity == null || !$this->get('mautic.security')->hasEntityAccess(
                'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($this->request->query->get('template'));
        $slots    = $this->factory->getTheme($template)->getSlots('page');

        //merge any existing changes
        $newContent = $this->get('session')->get('mautic.pagebuilder.'.$objectId.'.content', []);
        $content    = $entity->getContent();

        if (is_array($newContent)) {
            $content = array_merge($content, $newContent);
            // Update the content for processSlots
            $entity->setContent($content);
        }

        $this->processSlots($slots, $entity);

        $logicalName = $this->factory->getHelper('theme')->checkForTwigTemplate(':'.$template.':page.html.php');

        return $this->render($logicalName, [
            'isNew'       => $isNew,
            'slots'       => $slots,
            'formFactory' => $this->get('form.factory'),
            'content'     => $content,
            'page'        => $entity,
            'template'    => $template,
            'basePath'    => $this->request->getBasePath(),
        ]);
    }

    /**
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function abtestAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model  = $this->getModel('page.page');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            $parent = $entity->getVariantParent();

            if ($parent || !$this->get('mautic.security')->isGranted('page:pages:create') ||
                !$this->get('mautic.security')->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;

            //reset
            $clone->setHits(0);
            $clone->setRevision(0);
            $clone->setVariantHits(0);
            $clone->setUniqueHits(0);
            $clone->setVariantStartDate(null);
            $clone->setIsPublished(false);
            $clone->setVariantParent($entity);
        }

        return $this->newAction($clone);
    }

    /**
     * Make the variant the main.
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function winnerAction($objectId)
    {
        //todo - add confirmation to button click
        $page      = $this->get('session')->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', ['page' => $page]);
        $flashes   = [];

        $postActionVars = [
            'returnUrl'       => $returnUrl,
            'viewParameters'  => ['page' => $page],
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => [
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model  = $this->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = [
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'page:pages:editown',
                'page:pages:editother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->convertVariant($entity);

            $flashes[] = [
                'type'    => 'notice',
                'msg'     => 'mautic.page.notice.activated',
                'msgVars' => [
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId,
                ],
            ];

            $postActionVars['viewParameters'] = [
                'objectAction' => 'view',
                'objectId'     => $objectId,
            ];
            $postActionVars['returnUrl']       = $this->generateUrl('mautic_page_action', $postActionVars['viewParameters']);
            $postActionVars['contentTemplate'] = 'MauticPageBundle:Page:view';
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, [
                'flashes' => $flashes,
            ])
        );
    }

    /**
     * PreProcess page slots for public view.
     *
     * @param array $slots
     * @param Page  $entity
     */
    private function processSlots($slots, $entity)
    {
        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->get('templating.helper.assets');
        /** @var \Mautic\CoreBundle\Templating\Helper\SlotsHelper $slotsHelper */
        $slotsHelper = $this->get('templating.helper.slots');
        $formFactory = $this->get('form.factory');

        $slotsHelper->inBuilder(true);

        $content = $entity->getContent();

        foreach ($slots as $slot => $slotConfig) {
            // backward compatibility - if slotConfig array does not exist
            if (is_numeric($slot)) {
                $slot       = $slotConfig;
                $slotConfig = [];
            }

            // define default config if does not exist
            if (!isset($slotConfig['type'])) {
                $slotConfig['type'] = 'html';
            }

            if (!isset($slotConfig['placeholder'])) {
                $slotConfig['placeholder'] = 'mautic.page.builder.addcontent';
            }

            $value = isset($content[$slot]) ? $content[$slot] : '';

            if ($slotConfig['type'] == 'slideshow') {
                if (isset($content[$slot])) {
                    $options = json_decode($content[$slot], true);
                } else {
                    $options = [
                        'width'            => '100%',
                        'height'           => '250px',
                        'background_color' => 'transparent',
                        'arrow_navigation' => false,
                        'dot_navigation'   => true,
                        'interval'         => 5000,
                        'pause'            => 'hover',
                        'wrap'             => true,
                        'keyboard'         => true,
                    ];
                }

                // Create sample slides for first time or if all slides were deleted
                if (empty($options['slides'])) {
                    $options['slides'] = [
                        [
                            'order'            => 0,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_lb200.png'),
                            'captionheader'    => 'Caption 1',
                        ],
                        [
                            'order'            => 1,
                            'background-image' => $assetsHelper->getUrl('media/images/mautic_logo_db200.png'),
                            'captionheader'    => 'Caption 2',
                        ],
                    ];
                }

                // Order slides
                usort(
                    $options['slides'],
                    function ($a, $b) {
                        return strcmp($a['order'], $b['order']);
                    }
                );

                $options['slot']   = $slot;
                $options['public'] = false;

                // create config form
                $options['configForm'] = $formFactory->createNamedBuilder(
                    null,
                    'slideshow_config',
                    [],
                    ['data' => $options]
                )->getForm()->createView();

                // create slide config forms
                foreach ($options['slides'] as $key => &$slide) {
                    $slide['key']  = $key;
                    $slide['slot'] = $slot;
                    $slide['form'] = $formFactory->createNamedBuilder(
                        null,
                        'slideshow_slide_config',
                        [],
                        ['data' => $slide]
                    )->getForm()->createView();
                }

                $renderingEngine = $this->get('templating');

                if (method_exists($renderingEngine, 'getEngine')) {
                    $renderingEngine->getEngine('MauticPageBundle:Page:Slots/slideshow.html.php');
                }
                $slotsHelper->set($slot, $renderingEngine->render('MauticPageBundle:Page:Slots/slideshow.html.php', $options));
            } else {
                $slotsHelper->set($slot, "<div data-slot=\"text\" id=\"slot-{$slot}\">{$value}</div>");
            }
        }

        //add builder toolbar
        $slotsHelper->start('builder'); ?>
        <input type="hidden" id="builder_entity_id" value="<?php echo $view->escape($entity->getSessionId()); ?>"/>
        <?php
        $slotsHelper->stop();
    }
}
