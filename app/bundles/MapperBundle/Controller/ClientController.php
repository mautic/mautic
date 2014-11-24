<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\MapperBundle\Event\MapperAuthEvent;

class ClientController extends FormController
{
    /**
     * @param        $bundle
     * @param        $objectAction
     * @param int    $objectId
     * @param string $objectModel
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeClientAction($application, $objectAction, $objectId = 0, $objectModel = '') {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($application, $objectId, $objectModel);
        } else {
            return $this->accessDenied();
        }
    }

    public function indexAction($application, $page = 1)
    {
        $session = $this->factory->getSession();

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            $application.':mapper:view',
            $application.':mapper:create',
            $application.':mapper:edit',
            $application.':mapper:delete'
        ), "RETURN_ARRAY");

        if (!$permissions[$application.':mapper:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setTableOrder();
        }

        $viewParams = array(
            'page'   => $page,
            'application' => $application
        );

        //set limits
        $limit = $session->get('mautic.mapper.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get('mautic.mapper.filter', ''));
        $session->set('mautic.mapper.filter', $search);

        $filter = array('string' => $search, 'force' => array(
            array(
                'column' => 'e.application',
                'expr'   => 'eq',
                'value'  => $application
            )
        ));

        $orderBy    = $this->factory->getSession()->get('mautic.mapper.orderby', 'e.title');
        $orderByDir = $this->factory->getSession()->get('mautic.mapper.orderbydir', 'DESC');

        $entities = $this->factory->getModel('mapper.ApplicationClient')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($entities);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $viewParams['page'] = $lastPage;
            $session->set('mautic.mapper.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_mapper_client_index', $viewParams);

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticMapperBundle:Client:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_'.$application.'client_index',
                    'mauticContent' => 'clients'
                )
            ));
        }

        $tmpl = $this->request->get('tmpl', 'index');

        return $this->delegateView(array(
            'returnUrl'       => $this->generateUrl('mautic_mapper_client_index', $viewParams),
            'viewParameters'  => array(
                'application' => $application,
                'searchValue' => $search,
                'items'       => $entities,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticMapperBundle:Client:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_'.$application.'client_index',
                'mauticContent'  => 'clients',
                'route'          => $this->generateUrl('mautic_mapper_client_index', $viewParams)
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ($application)
    {
        $session = $this->factory->getSession();
        $model   = $this->factory->getModel('mapper.ApplicationClient');
        $entity  = $model->getEntity();

        if (!$this->factory->getSecurity()->isGranted($application.':mapper:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page   = $session->get('mautic.mapper.page', 1);
        $action = $this->generateUrl('mautic_mapper_client_action', array(
            'objectAction' => 'new',
            'application'  => $application
        ));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array('application' => $application));

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.core.notice.created', array(
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_mapper_index',
                            '%url%'       => $this->generateUrl('mautic_mapper_client_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId(),
                                'application'  => $application
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
                    'application' => $application
                );
                return $this->postActionRedirect(array(
                    'returnUrl'       => $this->generateUrl('mautic_mapper_client_index', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticMapperBundle:Client:index',
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_'.$application.'client_index',
                        'mauticContent' => 'client'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters' => array(
                'form'   => $form->createView(),
                'application' => $application
            ),
            'contentTemplate' => 'MauticMapperBundle:Client:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$application.'client_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_mapper_client_action', array(
                        'objectAction' => 'new',
                        'application'  => $application
                ))
            )
        ));
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($application, $objectId, $ignorePost = false)
    {
        $session    = $this->factory->getSession();
        $model      = $this->factory->getModel('mapper.ApplicationClient');
        $entity     = $model->getEntity($objectId);

        //set the page we came from
        $page       = $session->get('mautic.mapper.page', 1);
        $viewParams = array(
            'page'   => $page,
            'application' => $application
        );
        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_mapper_client_index', $viewParams);

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticMapperBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$application.'client_index',
                'mauticContent' => 'client'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.mapper.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->factory->getSecurity()->isGranted($application.':mapper:view')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'mapper.ApplicationClient');
        }

        //Create the form
        $action = $this->generateUrl('mautic_mapper_client_action', array(
            'objectAction' => 'edit',
            'objectId'     => $objectId,
            'application'  => $application
        ));
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array('application' => $application));
        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form) ){
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.core.notice.updated', array(
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_mapper_index',
                            '%url%'       => $this->generateUrl('mautic_mapper_client_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                    'application'  => $application
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
                        'returnUrl'       => $this->generateUrl('mautic_mapper_client_index', $viewParams),
                        'viewParameters'  => $viewParams,
                        'contentTemplate' => 'MauticMapperBundle:Client:index'
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
                'application'    => $application
            ),
            'contentTemplate' => 'MauticMapperBundle:Client:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_page_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_mapper_client_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId(),
                        'application'  => $application
                    ))
            )
        ));
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($application, $objectId)
    {
        $session     = $this->factory->getSession();
        $page        = $session->get('mautic.mapper.page', 1);
        $viewParams = array(
            'page'   => $page,
            'application' => $application
        );
        $returnUrl   = $this->generateUrl('mautic_mapper_client_index', $viewParams);
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticMapperBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$application.'client_index',
                'mauticContent' => 'client'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->factory->getModel('mapper.ApplicationClient');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.mapper.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted($application.':mapper:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'mapper.ApplicationClient');
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
     * @param string $bundle
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction($application) {
        $session     = $this->factory->getSession();
        $page        = $session->get('mautic.mapper.page', 1);
        $viewParams  = array(
            'page'   => $page,
            'application' => $application
        );
        $returnUrl   = $this->generateUrl('mautic_mapper_client_index', $viewParams);
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParams,
            'contentTemplate' => 'MauticMapperBundle:Client:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_'.$application.'client_index',
                'mauticContent' => 'client'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('mapper.ApplicationClient');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.mapper.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->isGranted($application.':mapper:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'ApplicationClient', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.mapper.notice.batch_deleted',
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
     * @param $application
     * @param $client
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function oAuth2CallbackAction($application, $client)
    {
        $event = new MapperAuthEvent($this->factory->getSecurity());
        $event->setApplication($application);
        $event->setClient($client);
        $this->factory->getDispatcher()->dispatch(MapperEvents::CALLBACK_API, $event);
        $postActionVars = $event->getPostActionRedirect();

        $viewParams = array(
            'client'   => $client,
            'application' => $application
        );

        if (!isset($postActionVars['returnUrl'])) {
            $postActionVars['returnUrl'] = $this->generateUrl('mautic_mapper_client_objects_index', $viewParams);
        }

        if (!isset($postActionVars['viewParameters'])) {
            $postActionVars['viewParameters'] = $viewParams;
        }

        if (!isset($postActionVars['contentTemplate'])) {
            $postActionVars['contentTemplate'] = 'MauticMapperBundle:Mapper:index';
        }

        return $this->postActionRedirect($postActionVars);
    }
}
