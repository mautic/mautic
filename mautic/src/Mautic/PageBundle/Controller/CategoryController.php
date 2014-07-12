<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryController extends FormController
{

    /**
     * @param int    $page
     * @param string $view
     * @param bool   $activeCategory
     * @param bool   $formView
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1 , $view = 'list', $activeCategory = false, $formView = false)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(array(
            'page:categories:view',
            'page:categories:create',
            'page:categories:edit',
            'page:categories:delete'
        ), "RETURN_ARRAY");

        if (!$permissions['page:categories:view']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.pagecategory.limit', $this->get('mautic.factory')->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->get('session')->get('mautic.pagecategory.filter', ''));
        $this->get('session')->set('mautic.pagecategory.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        $entities = $this->get('mautic.factory')->getModel('page.category')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderByDir' => "DESC",
                'getTotalCount' => true
            ));

        $count = $entities['totalCount'];
        unset($entities['totalCount']);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.pagecategory.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_pagecategory_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticPageBundle:Category:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_pagecategory_index',
                    'mauticContent' => 'pagecategory'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.pagecategory.page', $page);

        //get active form
        if ($activeCategory === false)
            $activeCategory = ($count) ? $entities[0] : false;

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $parameters = array(
            'searchValue' => $search,
            'items'       => $entities,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'permissions' => $permissions,
            'activeCategory'  => $activeCategory,
            'tmpl'        => $tmpl,
            'security'    => $this->get('mautic.security')
        );

        $vars = array(
            'activeLink'    => '#mautic_pagecategory_index',
            'mauticContent' => 'pagecategory',
            'route'         => $this->generateUrl('mautic_pagecategory_index', array('page' => $page))
        );

        if ($tmpl == "index") {
            switch ($view) {
                case 'list':
                    $template = 'MauticPageBundle:Category:details.html.php';
                    break;
                case 'view':
                    $template = 'MauticPageBundle:Category:details.html.php';
                    $vars['route'] = $this->generateUrl('mautic_pagecategory_action', array(
                            'objectAction' => 'view',
                            'objectId'     => $activeCategory->getId())
                    );
                    break;
                case 'edit':
                case 'new':
                    $template      = 'MauticPageBundle:Category:form.html.php';
                    $vars['route'] = $this->generateUrl('mautic_pagecategory_action', array(
                            'objectAction' => $view,
                            'objectId'     => $activeCategory->getId())
                    );
                    $parameters['form'] = $formView;
                    break;
            }
        } elseif ($tmpl == 'page') {
            $template       = 'MauticPageBundle:Category:details.html.php';
            $vars['target'] = '.bundle-main-inner-wrapper';
            $vars['route']  = $this->generateUrl('mautic_pagecategory_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activeCategory->getId())
            );
        } else {
            $template      = 'MauticPageBundle:Category:list.html.php';
            if ($tmpl == 'list') {
                $vars['target'] = '.bundle-list';
            }
            $parameters['dateFormat'] = $this->get('mautic.factory')->getParameter('date_format_full');
        }

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => $template,
            'passthroughVars' => $vars
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
        $activeCategory  = $this->get('mautic.factory')->getModel('page.category')->getEntity($objectId);
        //set the page we came from
        $page    = $this->get('session')->get('mautic.pagecategory.page', 1);

        if ($activeCategory === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_pagecategory_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticPageBundle:Category:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_pagecategory_index',
                    'mauticContent' => 'pagecategory'
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.page.category.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$this->get('mautic.security')->isGranted('page:categories:view')) {
            return $this->accessDenied();
        }

        return $this->indexAction($page, 'view', $activeCategory);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $model   = $this->get('mautic.factory')->getModel('page.category');
        $entity  = $model->getEntity();

        if (!$this->get('mautic.security')->isGranted('page:categories:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $this->get('session')->get('mautic.pagecategory.page', 1);
        $action = $this->generateUrl('mautic_pagecategory_action', array('objectAction' => 'new'));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.page.category.notice.created', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'          => $this->generateUrl('mautic_pagecategory_action', array(
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
                        $returnUrl      = $this->generateUrl('mautic_pagecategory_action', $viewParameters);
                        $template       = 'MauticPageBundle:Category:view';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_pagecategory_index', $viewParameters);
                $template  = 'MauticPageBundle:Category:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_pagecategory_index',
                        'mauticContent' => 'pagecategory'
                    )
                ));
            }
        }

        return $this->indexAction($page, 'new', $entity, $form->createView());
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model      = $this->get('mautic.factory')->getModel('page.category');
        $entity     = $model->getEntity($objectId);
        //set the page we came from
        $page    = $this->get('session')->get('mautic.pagecategory.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_pagecategory_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_pagecategory_index',
                'mauticContent' => 'pagecategory'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.page.category.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->get('mautic.security')->isGranted('page:categories:view')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'page.category');
        }

        //Create the form
        $action = $this->generateUrl('mautic_pagecategory_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.page.category.notice.updated', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'  => $this->generateUrl('mautic_pagecategory_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_pagecategory_action', array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        )),
                        'viewParameters'  => array('objectId' => $entity->getId()),
                        'contentTemplate' => 'MauticPageBundle:Category:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->indexAction($page, 'edit', $entity, $form->createView());
    }

    /**
     * Clone an entity
     *
     * @param $objectId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction ($objectId)
    {
        $model   = $this->get('mautic.factory')->getModel('page.category');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted('page:categories:create')) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
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
        $page        = $this->get('session')->get('mautic.pagecategory.page', 1);
        $returnUrl   = $this->generateUrl('mautic_pagecategory_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticPageBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_pagecategory_index',
                'mauticContent' => 'pagecategory'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->get('mautic.factory')->getModel('page.category');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.page.category.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->isGranted('page:categories:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'page.category');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.page.category.notice.deleted',
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
}