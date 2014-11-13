<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApplicationClientController extends FormController
{
    /**
     * @param        $bundle
     * @param        $objectAction
     * @param int    $objectId
     * @param string $objectModel
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeApplicationClientAction($bundle, $objectAction, $objectId = 0, $objectModel = '') {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($bundle, $objectId, $objectModel);
        } else {
            return $this->accessDenied();
        }
    }

    /**
     * @param     $bundle
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($bundle, $page = 1)
    {
        $session = $this->factory->getSession();

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            $bundle.':client:view',
            $bundle.':client:create',
            $bundle.':client:edit',
            $bundle.':client:delete'
        ), "RETURN_ARRAY");

        if (!$permissions[$bundle.':client:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setTableOrder();
        }

        $viewParams = array(
            'page'   => $page,
            'bundle' => $bundle
        );

        //set limits
        $limit = $session->get('mautic.mapper.client.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.mapper.client.filter', ''));
        $session->set('mautic.client.filter', $search);

        $filter     = array('string' => $search, 'force' => array(
            array(
                'column' => 'c.bundle',
                'expr'   => 'eq',
                'value'  => $bundle
            )
        ));

        $orderBy    = $this->factory->getSession()->get('mautic.mapper.client.orderby', 'c.title');
        $orderByDir = $this->factory->getSession()->get('mautic.mapper.client.orderbydir', 'DESC');

        $entities = $this->factory->getModel('mapper.ApplicationClient')->getEntities(
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
            $viewParams['page'] = $lastPage;
            $session->set('mautic.mapper.client.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_mapper_client_index', $viewParams);

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticMapperBundle:ApplicationClient:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_'.$bundle.'client_index',
                    'mauticContent' => 'category'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set('mautic.category.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_mapper_client_index', $viewParams),
            'viewParameters'  => array(
                'bundle'      => $bundle,
                'searchValue' => $search,
                'items'       => $entities,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticCategoryBundle:Category:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_'.$bundle.'category_index',
                'mauticContent'  => 'category',
                'route'          => $this->generateUrl('mautic_category_index', $viewParams)
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ($bundle)
    {
        $session = $this->factory->getSession();
        $model   = $this->factory->getModel('category.category');
        $entity  = $model->getEntity();

        if (!$this->factory->getSecurity()->isGranted($bundle.':categories:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.category.page', 1);
        $action = $this->generateUrl('mautic_category_action', array(
            'objectAction' => 'new',
            'bundle'       => $bundle
        ));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array('bundle' => $bundle));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.category.notice.created', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'          => $this->generateUrl('mautic_category_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                    'bundle'       => $bundle
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
                $viewParameters  = array(
                    'page'   => $page,
                    'bundle' => $bundle
                );
                return $this->postActionRedirect(array(
                    'returnUrl'       => $this->generateUrl('mautic_category_index', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticCategoryBundle:Category:index',
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_'.$bundle.'category_index',
                        'mauticContent' => 'category'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters' => array(
                'form'   => $form->createView(),
                'bundle' => $bundle,
            ),
            'contentTemplate' => 'MauticCategoryBundle:Category:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_category_action', array(
                        'objectAction' => 'new',
                        'bundle'       => $bundle
                    ))
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($bundle, $objectId, $ignorePost = false)
    {
        $session    = $this->factory->getSession();
        $model      = $this->factory->getModel('category.category');
        $entity     = $model->getEntity($objectId);
        //set the page we came from
        $page       = $session->get('mautic.category.page', 1);
        $viewParams = array(
            'page'   => $page,
            'bundle' => $bundle
        );
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_category_index', $viewParams);

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticCategoryBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.category.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->factory->getSecurity()->isGranted($bundle.':categories:view')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'category.category');
        }

        //Create the form
        $action = $this->generateUrl('mautic_category_action', array(
            'objectAction' => 'edit',
            'objectId'     => $objectId,
            'bundle'       => $bundle
        ));
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array('bundle' => $bundle));

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.category.notice.updated', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'  => $this->generateUrl('mautic_category_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                    'bundle'       => $bundle
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
                        'returnUrl'       => $this->generateUrl('mautic_category_index', $viewParams),
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => 'MauticCategoryBundle:Category:index'
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
                'activeCategory' => $entity,
                'bundle'         => $bundle
            ),
            'contentTemplate' => 'MauticCategoryBundle:Category:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_category_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                        'bundle'       => $bundle
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
    public function cloneAction ($bundle, $objectId)
    {
        $model   = $this->factory->getModel('category.category');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted($bundle.':categories:create')) {
                return $this->accessDenied();
            }

            $clone = clone $entity;
            $clone->setIsPublished(false);
            $model->saveEntity($clone);
            $objectId = $clone->getId();
        }

        return $this->editAction($bundle, $objectId);
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($bundle, $objectId) {
        $session     = $this->factory->getSession();
        $page        = $session->get('mautic.category.page', 1);
        $viewParams = array(
            'page'   => $page,
            'bundle' => $bundle
        );
        $returnUrl   = $this->generateUrl('mautic_category_index', $viewParams);
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticCategoryBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('category.category');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.category.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted($bundle.':categories:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'category.category');
            }

            $model->deleteEntity($entity);

            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.category.notice.deleted',
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
     * @param string $bundle
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction($bundle) {
        $session     = $this->factory->getSession();
        $page        = $session->get('mautic.category.page', 1);
        $viewParams  = array(
            'page'   => $page,
            'bundle' => $bundle
        );
        $returnUrl   = $this->generateUrl('mautic_category_index', $viewParams);
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticCategoryBundle:Category:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$bundle.'category_index',
                'mauticContent' => 'category'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('category');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.category.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->isGranted($bundle.':categories:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'category', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.category.notice.batch_deleted',
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
}
