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
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpFoundation\JsonResponse;

class PageController extends FormController
{

    /**
     * @param int    $page
     * @param string $view
     * @param bool   $activePage
     * @param bool   $formView
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1 , $view = 'list', $activePage = false, $formView = false)
    {
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
        $model = $this->get('mautic.factory')->getModel('page.page');
        $pages = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderByDir' => "DESC",
                'getTotalCount' => true
            ));

        $count = $pages['totalCount'];
        unset($pages['totalCount']);
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

        //get active form
        if ($activePage === false)
            $activePage = ($count) ? $pages[0] : false;

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->get('mautic.factory')->getModel('page.page')->getLookupResults('category', '', 0);

        $parameters = array(
            'searchValue' => $search,
            'items'       => $pages,
            'categories'  => $categories,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'permissions' => $permissions,
            'activePage'  => $activePage,
            'pageUrl'     => (!empty($activePage)) ? $model->generateUrl($activePage) : '',
            'tmpl'        => $tmpl,
            'security'    => $this->get('mautic.security')
        );

        $vars = array(
            'activeLink'    => '#mautic_page_index',
            'mauticContent' => 'page',
            'route'         => $this->generateUrl('mautic_page_index', array('page' => $page))
        );

        if ($tmpl == "index") {
            switch ($view) {
                case 'list':
                    $template = 'MauticPageBundle:Page:details.html.php';
                    break;
                case 'view':
                    $template = 'MauticPageBundle:Page:details.html.php';
                    $vars['route'] = $this->generateUrl('mautic_page_action', array(
                            'objectAction' => 'view',
                            'objectId'     => $activePage->getId())
                    );
                    break;
                case 'edit':
                case 'new':
                    $template      = 'MauticPageBundle:Page:form.html.php';
                    $vars['route'] = $this->generateUrl('mautic_page_action', array(
                            'objectAction' => $view,
                            'objectId'     => $activePage->getId())
                    );
                    $parameters['form']   = $formView;
                    $parameters['tokens'] = $model->getPageTokens();
                    break;
            }
        } elseif ($tmpl == 'page') {
            $template       = 'MauticPageBundle:Page:details.html.php';
            $vars['target'] = '.bundle-main-inner-wrapper';
            $vars['route']  = $this->generateUrl('mautic_page_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activePage->getId())
            );
        } else {
            $template      = 'MauticPageBundle:Page:list.html.php';
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
        $activePage  = $this->get('mautic.factory')->getModel('page.page')->getEntity($objectId);
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

        return $this->indexAction($page, 'view', $activePage);
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

        return $this->indexAction($page, 'new', $entity, $form->createView());
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
                }
            } else {
                //clear any modified content
                $session->remove('mautic.pagebuilder.'.$objectId.'.content', array());
                //unlock the entity
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_page_action', array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        )),
                        'viewParameters'  => array('objectId' => $entity->getId()),
                        'contentTemplate' => 'MauticPageBundle:Page:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);

            //set the lookup values
            $parent = $entity->getParent();
            if ($parent)
                $form->get('parent_lookup')->setData($parent->getTitle());
            $category = $entity->getCategory();
            if ($category)
                $form->get('category_lookup')->setData($category->getTitle());
        }

        return $this->indexAction($page, 'edit', $entity, $form->createView());
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
        $model  = $this->get('mautic.factory')->getModel('page.page');

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

        //get the slots from the config file
        $kernelDir  = $this->container->getParameter('kernel.root_dir');
        $configFile = $kernelDir . '/Resources/views/Templates/'.$template.'/config.php';

        if (!file_exists($configFile)) {
            return $this->accessDenied('mautic.page.page.error.template.notfound');
        }

        $tmplConfig = include_once $configFile;
        $slots      = $tmplConfig['slots']['page'];

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
}