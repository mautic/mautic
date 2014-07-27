<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - fix issue where associations are not populating immediately after an edit

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class PageController extends FormController
{

    /**
     * @param int    $page
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        $model = $this->get('mautic.factory')->getModel('page.page');

        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(array(
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

        //set limits
        $limit = $this->get('session')->get('mautic.page.limit', $this->get('mautic.factory')->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.page.filter', ''));
        $this->get('session')->set('mautic.page.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['page:pages:viewother']) {
            $filter['force'][] =
                array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->get('mautic.factory')->getUser());
        }

        $translator = $this->get('translator');
        //do not list variants in the main list
        $filter['force'][] = array('column' => 'p.variantParent', 'expr' => 'isNull');

        $langSearchCommand = $translator->trans('mautic.page.page.searchcommand.lang');
        if (strpos($search, "{$langSearchCommand}:") === false) {
            $filter['force'][] = array('column' => 'p.translationParent', 'expr' => 'isNull');
        }

        $orderBy     = $this->get('session')->get('mautic.page.orderby', 'p.title');
        $orderByDir  = $this->get('session')->get('mautic.page.orderbydir', 'DESC');

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
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.page.page', $lastPage);
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
        $this->get('session')->set('mautic.page.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->get('mautic.factory')->getModel('page.page')->getLookupResults('category', '', 0);

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
                'security'    => $this->get('mautic.security')
            ),
            'contentTemplate' => 'MauticPageBundle:Page:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_page_index',
                'mauticContent'  => 'page',
                'route'          => $this->generateUrl('mautic_page_index', array('page' => $page)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }

    /**
     * Loads a specific form into the detailed panel
     *
     * @param $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        $factory     = $this->get('mautic.factory');
        $model       = $factory->getModel('page.page');
        $security    = $factory->getSecurity();
        $activePage  = $model->getEntity($objectId);
        //set the page we came from
        $page        = $this->get('session')->get('mautic.page.page', 1);

        if ($activePage === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_page_index', array('page' => $page));

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
                        'type' => 'error',
                        'msg'  => 'mautic.page.page.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'page:pages:viewown', 'page:pages:viewother', $activePage->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_page_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activePage->getId())
            ),
            'viewParameters'  => array(
                'activePage' => $activePage,
                'permissions' => $security->isGranted(array(
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
                'stats'       => array(
                    'bounces' => $model->getBounces($activePage)
                ),
                'security' => $security,
                'dateFormat' => $factory->getParameter('date_format_full'),
                'pageUrl'   => $model->generateUrl($activePage, true)
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $model   = $this->get('mautic.factory')->getModel('page.page');
        $entity  = $model->getEntity();
        $method  = $this->request->getMethod();
        $session = $this->get('session');
        if (!$this->get('mautic.security')->isGranted('page:pages:create')) {
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
                    $session     = $this->get('session');
                    $contentName = 'mautic.pagebuilder.'.$entity->getSessionId().'.content';
                    $content = $session->get($contentName, array());
                    $entity->setContent($content);

                    //form is valid so process the data
                    $model->saveEntity($entity);

                    //clear the session
                    $session->remove($contentName);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.page.page.notice.created', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'          => $this->generateUrl('mautic_page_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );

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
                $session->remove('mautic.pagebuilder.'.$entity->getSessionId().'.content', array());
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

        $builderComponents    = $model->getBuilderComponents();
        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $form->createView(),
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model      = $this->get('mautic.factory')->getModel('page.page');
        $entity     = $model->getEntity($objectId);
        $session    = $this->get('session');
        $page       = $this->get('session')->get('mautic.page.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_page_index', array('page' => $page));

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
                            'msg'  => 'mautic.page.page.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->get('mautic.security')->hasEntityAccess(
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

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.page.page.notice.updated', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'  => $this->generateUrl('mautic_page_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );

                    $returnUrl = $this->generateUrl('mautic_page_action', array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId()
                    ));
                    $viewParams = array('objectId' => $entity->getId());
                    $template = 'MauticPageBundle:Page:view';
                }
            } else {
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$objectId.'.content', array());
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_page_index', array('page' => $page));
                $viewParams = array('page' => $page);
                $template  = 'MauticPageBundle:Page:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => $template
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);

            //set the lookup values
            $parent = $entity->getTranslationParent();
            if ($parent && isset($form['translationParent_lookup']))
                $form->get('translationParent_lookup')->setData($parent->getTitle());
            $category = $entity->getCategory();
            if ($category && isset($form['category_lookup']))
                $form->get('category_lookup')->setData($category->getTitle());
        }

        $formView = $this->setFormTheme($form, 'MauticPageBundle:Page:form.html.php', 'MauticPageBundle:FormVariant');

        $builderComponents    = $model->getBuilderComponents();
        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $formView,
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
     * @param $objectId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        $model   = $this->get('mautic.factory')->getModel('page.page');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('page:pages:create') ||
                !$this->get('mautic.security')->hasEntityAccess(
                    'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setHits(0);
            $clone->setRevision(0);
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        $page        = $this->get('session')->get('mautic.page.page', 1);
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
            $model  = $this->get('mautic.factory')->getModel('page.page');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.page.page.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->isGranted('page:pages:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.page');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.page.page.notice.deleted',
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
     * Activate the builder
     *
     * @param $objectId
     */
    public function builderAction($objectId)
    {
        $factory = $this->get('mautic.factory');
        $model   = $factory->getModel('page.page');

        //permission check
        if (strpos($objectId, 'new') !== false) {
            $isNew = true;
            if (!$this->get('mautic.security')->isGranted('page:pages:create')) {
                return $this->accessDenied();
            }
            $entity = $model->getEntity();
            $entity->setSessionId($objectId);
        } else {
            $isNew    = false;
            $entity = $model->getEntity($objectId);
            if (!$this->get('mautic.security')->hasEntityAccess(
                'page:pages:viewown', 'page:pages:viewother', $entity->getCreatedBy()
            )) {
                return $this->accessDenied();
            }
        }

        $template = InputHelper::clean($this->request->query->get('template'));
        $slots    = $factory->getTheme($template)->getSlots('page');

        //merge any existing changes
        $newContent = $this->get('session')->get('mautic.pagebuilder.'.$objectId.'.content', array());
        $content    = $entity->getContent();
        $content    = array_merge($content, $newContent);
        return $this->render('MauticPageBundle::builder.html.php', array(
            'isNew'    => $isNew,
            'slots'    => $slots,
            'content'  => $content,
            'page'     => $entity,
            'template' => $template,
            'basePath' => $this->request->getBasePath()
        ));
    }

    public function abtestAction($objectId)
    {
        $model   = $this->get('mautic.factory')->getModel('page.page');
        $entity  = $model->getEntity($objectId);

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
            $clone->setIsPublished(false);
            $clone->setVariantParent($entity);

            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($objectId);
    }
}