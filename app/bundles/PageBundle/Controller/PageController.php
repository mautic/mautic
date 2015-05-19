<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PageController
 */
class PageController extends FormController
{

    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        $model = $this->factory->getModel('page.page');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'page:pages:viewown',
            'page:pages:viewother',
            'page:pages:create',
            'page:pages:editown',
            'page:pages:editother',
            'page:pages:deleteown',
            'page:pages:deleteother',
            'page:pages:publishown',
            'page:pages:publishother'
        ), "RETURN_ARRAY");

        if (!$permissions['page:pages:viewown'] && !$permissions['page:pages:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.page.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.page.filter', ''));
        $this->factory->getSession()->set('mautic.page.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['page:pages:viewother']) {
            $filter['force'][] = array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $translator = $this->get('translator');

        //do not list variants in the main list
        $filter['force'][] = array('column' => 'p.variantParent', 'expr' => 'isNull');

        $langSearchCommand = $translator->trans('mautic.core.searchcommand.lang');
        if (strpos($search, "{$langSearchCommand}:") === false) {
            $filter['force'][] = array('column' => 'p.translationParent', 'expr' => 'isNull');
        }

        $orderBy    = $this->factory->getSession()->get('mautic.page.orderby', 'p.title');
        $orderByDir = $this->factory->getSession()->get('mautic.page.orderbydir', 'DESC');

        $pages = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($pages);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (floor($limit / $count)) ?: 1;
            $this->factory->getSession()->set('mautic.page.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_page_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticPageBundle:Page:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.page.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->factory->getModel('page.page')->getLookupResults('category', '', 0);

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $pages,
                'categories'  => $categories,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticPageBundle:Page:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_page_index',
                'mauticContent'  => 'page',
                'route'          => $this->generateUrl('mautic_page_index', array('page' => $page))
            )
        ));
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model      = $this->factory->getModel('page.page');
        $security   = $this->factory->getSecurity();
        $activePage = $model->getEntity($objectId);
        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.page.page', 1);

        if ($activePage === null) {
            //set the return URL
            $returnUrl = $this->generateUrl('mautic_page_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticPageBundle:Page:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_page_index',
                    'mauticContent' => 'page'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $activePage->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }

        //get A/B test information
        list($parent, $children) = $model->getVariants($activePage);
        $properties   = array();
        $variantError = false;
        $weight       = 0;
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

            $properties[$parent->getId()]['weight'] = 100 - $weight;
            $properties[$parent->getId()]['winnerCriteria'] = '';
        }

        $abTestResults = array();
        $criteria = $model->getBuilderComponents($activePage, 'abTestWinnerCriteria');
        if (!empty($lastCriteria) && empty($variantError)) {
            //there is a criteria to compare the pages against so let's shoot the page over to the criteria function to do its thing
            if (isset($criteria['criteria'][$lastCriteria])) {
                $testSettings = $criteria['criteria'][$lastCriteria];

                $args = array(
                    'factory'    => $this->factory,
                    'page'       => $activePage,
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties
                );

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

                    $pass = array();
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

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('page', $activePage->getId());

        // Hit count per day for last 30 days
        $last30 = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit')->getHits(30, 'D', array('page_id' => $activePage->getId()));

        //get related translations
        list($translationParent, $translationChildren) = $model->getTranslations($activePage);

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_page_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activePage->getId())
            ),
            'viewParameters'  => array(
                'activePage'    => $activePage,
                'variants'      => array(
                    'parent'     => $parent,
                    'children'   => $children,
                    'properties' => $properties,
                    'criteria'   => $criteria['criteria']
                ),
                'translations'  => array(
                    'parent'   => $translationParent,
                    'children' => $translationChildren
                ),
                'permissions'   => $security->isGranted(array(
                    'page:pages:viewown',
                    'page:pages:viewother',
                    'page:pages:create',
                    'page:pages:editown',
                    'page:pages:editother',
                    'page:pages:deleteown',
                    'page:pages:deleteother',
                    'page:pages:publishown',
                    'page:pages:publishother'
                ), "RETURN_ARRAY"),
                'stats'         => array(
                    'bounces'   => $model->getBounces($activePage),
                    'hits'      => array(
                        'total'  => $activePage->getHits(),
                        'unique' => $activePage->getUniqueHits()
                    ),
                    'dwellTime' => $model->getDwellTimeStats($activePage)
                ),
                'abTestResults' => $abTestResults,
                'security'      => $security,
                'pageUrl'       => $model->generateUrl($activePage, true),
                'logs'          => $logs,
                'last30'        => $last30
            ),
            'contentTemplate' => 'MauticPageBundle:Page:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page'
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page.page');
        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->factory->getSession();
        if (!$this->factory->getSecurity()->isGranted('page:pages:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.page.page', 1);
        $action = $this->generateUrl('mautic_page_action', array('objectAction' => 'new'));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $session     = $this->factory->getSession();
                    $contentName = 'mautic.pagebuilder.'.$entity->getSessionId().'.content';
                    $content = $session->get($contentName, array());
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    //clear the session
                    $session->remove($contentName);

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        );
                        $returnUrl      = $this->generateUrl('mautic_page_action', $viewParameters);
                        $template       = 'MauticPageBundle:Page:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_page_index', $viewParameters);
                $template  = 'MauticPageBundle:Page:index';
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$entity->getSessionId().'.content');
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_page_index',
                        'mauticContent' => 'page'
                    )
                ));
            }
        }

        $builderComponents    = $model->getBuilderComponents($entity);
        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $this->setFormTheme($form, 'MauticPageBundle:Page:form.html.php', 'MauticPageBundle:FormTheme\Page'),
                'tokens'      => $builderComponents['pageTokens'],
                'activePage'  => $entity
            ),
            'contentTemplate' => 'MauticPageBundle:Page:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', array(
                    'objectAction' => 'new'
                ))
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page.page');
        $entity  = $model->getEntity($objectId);
        $session = $this->factory->getSession();
        $page    = $this->factory->getSession()->get('mautic.page.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_page_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.page.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->factory->getSecurity()->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
        )) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'page.page');
        }

        //Create the form
        $action = $this->generateUrl('mautic_page_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $contentName     = 'mautic.pagebuilder.'.$entity->getSessionId().'.content';
                    $existingContent = $entity->getContent();
                    $newContent      = $session->get($contentName, array());
                    $content         = array_merge($existingContent, $newContent);
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    //clear the session
                    $session->remove($contentName);

                    $this->addFlash('mautic.core.notice.updated', array(
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_page_index',
                        '%url%'       => $this->generateUrl('mautic_page_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));
                }
            } else {
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$objectId.'.content');
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                );
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_page_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'MauticPageBundle:Page:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);

            //clear any modified content
            $session->remove('mautic.pagebuilder.'.$objectId.'.content');

            //set the lookup values
            $parent = $entity->getTranslationParent();
            if ($parent && isset($form['translationParent_lookup']))
                $form->get('translationParent_lookup')->setData($parent->getTitle());
        }

        $builderComponents    = $model->getBuilderComponents($entity);
        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $this->setFormTheme($form, 'MauticPageBundle:Page:form.html.php', 'MauticPageBundle:FormTheme\Page'),
                'tokens'      => $builderComponents['pageTokens'],
                'activePage'  => $entity
            ),
            'contentTemplate' => 'MauticPageBundle:Page:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_page_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function cloneAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page.page');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('page:pages:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setHits(0);
            $clone->setUniqueHits(0);
            $clone->setRevision(0);
            $clone->setVariantStartDate(null);
            $clone->setVariantHits(0);
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model  = $this->factory->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'page:pages:deleteown',
                'page:pages:deleteother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $entity->getTitle(),
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
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $page      = $this->factory->getSession()->get('mautic.page.page', 1);
        $returnUrl = $this->generateUrl('mautic_page_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model     = $this->factory->getModel('page');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.page.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
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

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.page.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }

    /**
     * Activate the builder
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function builderAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model = $this->factory->getModel('page.page');

        //permission check
        if (strpos($objectId, 'new') !== false) {
            $isNew = true;
            if (!$this->factory->getSecurity()->isGranted('page:pages:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew    = false;
            $entity = $model->getEntity($objectId);
            if (!$this->factory->getSecurity()->hasEntityAccess(
                'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($this->request->query->get('template'));
        $slots    = $this->factory->getTheme($template)->getSlots('page');

        //merge any existing changes
        $newContent = $this->factory->getSession()->get('mautic.pagebuilder.'.$objectId.'.content', array());
        $content    = $entity->getContent();
        if (is_array($newContent)) {
            $content = array_merge($content, $newContent);
        }

        return $this->render('MauticPageBundle::builder.html.php', array(
            'isNew'         => $isNew,
            'slots'         => $slots,
            'formFactory'   => $this->get('form.factory'),
            'content'       => $content,
            'page'          => $entity,
            'template'      => $template,
            'basePath'      => $this->request->getBasePath()
        ));
    }

    /**
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function abtestAction($objectId)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $model */
        $model   = $this->factory->getModel('page.page');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            $parent = $entity->getVariantParent();

            if ($parent || !$this->factory->getSecurity()->isGranted('page:pages:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
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

            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Make the variant the main
     *
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function winnerAction($objectId)
    {
        //todo - add confirmation to button click
        $page        = $this->factory->getSession()->get('mautic.page.page', 1);
        $returnUrl   = $this->generateUrl('mautic_page_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Page:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_page_index',
                'mauticContent' => 'page'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /** @var \Mautic\PageBundle\Model\PageModel $model */
            $model  = $this->factory->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.page.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'page:pages:editown',
                'page:pages:editother',
                $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->convertVariant($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.page.notice.activated',
                'msgVars' => array(
                    '%name%' => $entity->getTitle(),
                    '%id%'   => $objectId
                )
            );

            $postActionVars['viewParameters'] = array(
                'objectAction' => 'view',
                'objectId' => $objectId
            );
            $postActionVars['returnUrl']       = $this->generateUrl('mautic_page_action', $postActionVars['viewParameters']);
            $postActionVars['contentTemplate'] = 'MauticPageBundle:Page:view';

        } //else don't do anything

        return $this->postActionRedirect(
            array_merge($postActionVars, array(
                'flashes' => $flashes
            ))
        );
    }
}
