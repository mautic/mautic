<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryController extends FormController
{

    /**
     * @param int    $page
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        $session = $this->factory->getSession();

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'asset:categories:view',
            'asset:categories:create',
            'asset:categories:edit',
            'asset:categories:delete'
        ), "RETURN_ARRAY");

        if (!$permissions['asset:categories:view']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $session->get('mautic.assetcategory.limit', $this->factory->getParameter('default_assetlimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.assetcategory.filter', ''));
        $session->set('mautic.assetcategory.filter', $search);

        $filter     = array('string' => $search, 'force' => array());
        $orderBy    = $this->factory->getSession()->get('mautic.assetcategory.orderby', 'c.title');
        $orderByDir = $this->factory->getSession()->get('mautic.assetcategory.orderbydir', 'DESC');

        $entities = $this->factory->getModel('asset.category')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($entities);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $session->set('mautic.assetcategory.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_pagecategory_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticAssetBundle:Category:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_assetcategory_index',
                    'mauticContent' => 'assetcategory'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.assetcategory.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';


        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_assetcategory_index', array('page' => $page)),
            'viewParameters'  => array(
                'searchValue' => $search,
                'items'       => $entities,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl,
                'dateFormat'  => $this->factory->getParameter('date_format_full')
            ),
            'contentTemplate' => 'MauticAssetBundle:Category:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_assetcategory_index',
                'mauticContent'  => 'assetcategory',
                'replaceContent' => ($tmpl == 'list') ? 'true': 'false'
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
        $session = $this->factory->getSession();
        $model   = $this->factory->getModel('asset.category');
        $entity  = $model->getEntity();

        if (!$this->factory->getSecurity()->isGranted('asset:categories:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.assetcategory.page', 1);
        $action = $this->generateUrl('mautic_assetcategory_action', array('objectAction' => 'new'));

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
                        $this->get('translator')->trans('mautic.asset.category.notice.created', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'          => $this->generateUrl('mautic_assetcategory_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );

                    if (!$form->get('buttons')->get('save')->isClicked()) {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters  = array('page' => $page);
                return $this->postActionRedirect(array(
                    'returnUrl'       => $this->generateUrl('mautic_assetcategory_index', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticAssetBundle:Category:index',
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_assetcategory_index',
                        'mauticContent' => 'assetcategory'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters' => array(
                'form'           => $form->createView()
            ),
            'contentTemplate' => 'MauticAssetBundle:Category:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_assetcategory_action', array('objectAction' => 'new'))
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
        $session = $this->factory->getSession();
        $model   = $this->factory->getModel('asset.category');
        $entity  = $model->getEntity($objectId);
        //set the page we came from
        $page    = $session->get('mautic.assetcategory.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_assetcategory_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticAssetBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_assetcategory_index',
                'mauticContent' => 'assetcategory'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.asset.category.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->factory->getSecurity()->isGranted('asset:categories:view')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'asset.category');
        }

        //Create the form
        $action = $this->generateUrl('mautic_assetcategory_action', array('objectAction' => 'edit', 'objectId' => $objectId));
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
                        $this->get('translator')->trans('mautic.asset.category.notice.updated', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'  => $this->generateUrl('mautic_assetcategory_action', array(
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
                        'returnUrl'       => $this->generateUrl('mautic_assetcategory_index', array(
                            'page' => $page
                        )),
                        'viewParameters'  => array('page' => $page),
                        'contentTemplate' => 'MauticAssetBundle:Category:index'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(array(
            'viewParameters' => array(
                'form'           => $form->createView(),
                'activeCategory' => $entity
            ),
            'contentTemplate' => 'MauticAssetBundle:Category:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_asset_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_assetcategory_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId())
                )
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
        $model   = $this->factory->getModel('asset.category');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('asset:categories:create')) {
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
        $session     = $this->factory->getSession();
        $page        = $session->get('mautic.assetcategory.page', 1);
        $returnUrl   = $this->generateUrl('mautic_assetcategory_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticAssetBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_assetcategory_index',
                'mauticContent' => 'assetcategory'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('asset.category');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.asset.category.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted('asset:categories:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'asset.category');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.asset.category.notice.deleted',
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