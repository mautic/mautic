<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

/**
 * Class FormController
 */
class FormController extends CommonController
{
    // Used for the standard template functions
    protected $modelName;
    protected $permissionBase;
    protected $routeBase;
    protected $sessionBase;
    protected $langStringBase;
    protected $activeLink;
    protected $mauticContent;
    protected $templateBase;

    /**
     * Checks to see if the form was cancelled
     *
     * @param Form $form
     *
     * @return int
     */
    protected function isFormCancelled(Form &$form)
    {
        $name = $form->getName();

        return $this->request->request->get($name.'[buttons][cancel]', false, true) !== false;
    }

    /**
     * Checks to see if the form was applied or saved
     *
     * @param $form
     *
     * @return bool
     */
    protected function isFormApplied($form)
    {
        $name = $form->getName();

        return $this->request->request->get($name.'[buttons][apply]', false, true) !== false;
    }

    /**
     * Binds form data, checks validity, and determines cancel request
     *
     * @param Form $form
     *
     * @return int
     */
    protected function isFormValid(Form &$form)
    {
        //bind request to the form
        $form->handleRequest($this->request);

        return $form->isValid();
    }

    /**
     * Decide if current user can edit or can edit specific entity if entity is provided
     * For BC, if permissionBase property is not set, it allow to edit only to administrators.
     *
     * @param object $entity
     *
     * @return boolean
     */
    protected function canEdit($entity = null)
    {
        $security = $this->get('mautic.security');
        
        if ($this->permissionBase) {
            if ($entity && $security->checkPermissionExists($this->permissionBase.':editown')) {
                return $security->hasEntityAccess(
                    $this->permissionBase.':editown',
                    $this->permissionBase.':editother',
                    $entity->getCreatedBy()
                );
            } elseif ($security->checkPermissionExists($this->permissionBase.':edit')) {
                return $security->isGranted(
                    $this->permissionBase.':edit'
                );
            }
        }

        return $this->get('mautic.helper.user')->getUser()->isAdmin();
    }

    /**
     * Returns view to index with a locked out message
     *
     * @param array  $postActionVars
     * @param object $entity
     * @param string $model
     * @param bool   $batch Flag if a batch action is being performed
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|array
     */
    protected function isLocked($postActionVars, $entity, $model, $batch = false)
    {
        $date      = $entity->getCheckedOut();
        $returnUrl = !empty($postActionVars['returnUrl'])
            ?
            urlencode($postActionVars['returnUrl'])
            :
            urlencode($this->generateUrl('mautic_dashboard_index'));
        $override  = '';

        $modelClass   = $this->getModel($model);
        $nameFunction = $modelClass->getNameGetter();
        $this->permissionBase = $modelClass->getPermissionBase();

        if ($this->canEdit($entity)) {
            $override     = $this->get('translator')->trans(
                'mautic.core.override.lock',
                array(
                    '%url%' => $this->generateUrl(
                        'mautic_core_form_action',
                        array(
                            'objectAction' => 'unlock',
                            'objectModel'  => $model,
                            'objectId'     => $entity->getId(),
                            'returnUrl'    => $returnUrl,
                            'name'         => urlencode($entity->$nameFunction())
                        )
                    )
                )
            );
        }

        $flash = array(
            'type'    => 'error',
            'msg'     => 'mautic.core.error.locked',
            'msgVars' => array(
                "%name%"       => $entity->$nameFunction(),
                "%user%"       => $entity->getCheckedOutByUser(),
                '%contactUrl%' => $this->generateUrl(
                    'mautic_user_action',
                    array(
                        'objectAction' => 'contact',
                        'objectId'     => $entity->getCheckedOutBy(),
                        'entity'       => $model,
                        'id'           => $entity->getId(),
                        'subject'      => 'locked',
                        'returnUrl'    => $returnUrl
                    )
                ),
                '%date%'       => $date->format($this->coreParametersHelper->getParameter('date_format_dateonly')),
                '%time%'       => $date->format($this->coreParametersHelper->getParameter('date_format_timeonly')),
                '%datetime%'   => $date->format($this->coreParametersHelper->getParameter('date_format_full')),
                '%override%'   => $override
            )
        );
        if ($batch) {
            return $flash;
        }

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => array($flash)
                )
            )
        );
    }

    /**
     * @param int    $id
     * @param string $modelName
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function unlockAction($id, $modelName)
    {
        $model   = $this->getModel($modelName);
        $entity  = $model->getEntity($id);
        $this->permissionBase = $model->getPermissionBase();

        if ($this->canEdit($entity)) {
            
            if ($entity !== null && $entity->getCheckedOutBy() !== null) {
                $model->unlockEntity($entity);
            }
            $returnUrl = urldecode($this->request->get('returnUrl'));
            if (empty($returnUrl)) {
                $returnUrl = $this->generateUrl('mautic_dashboard_index');
            }

            $this->addFlash(
                'mautic.core.action.entity.unlocked',
                array(
                    '%name%' => urldecode($this->request->get('name'))
                )
            );

            return $this->redirect($returnUrl);
        }

        return $this->accessDenied();
    }

    /**
     * @param string $modelName      The model for this controller
     * @param string $permissionBase Permission base for the model (i.e. form.forms or addon.yourAddon.items)
     * @param string $routeBase      Route base for the controller routes (i.e. mautic_form or custom_addon)
     * @param string $sessionBase    Session name base for items saved to session such as filters, page, etc
     * @param string $langStringBase Language string base for the shared strings
     * @param string $templateBase   Template base (i.e. YourController:Default) for the view/controller
     * @param string $activeLink     Link ID to return via ajax response
     * @param string $mauticContent  Mautic content string to return via ajax response for onLoad functions
     */
    protected function setStandardParameters(
        $modelName,
        $permissionBase,
        $routeBase,
        $sessionBase,
        $langStringBase,
        $templateBase,
        $activeLink = null,
        $mauticContent = null
    ) {
        $this->modelName      = $modelName;
        $this->permissionBase = $permissionBase;
        if (strpos($sessionBase, 'mautic.') !== 0) {
            $sessionBase = 'mautic.' . $sessionBase;
        }
        $this->sessionBase    = $sessionBase;
        $this->routeBase      = $routeBase;
        $this->langStringBase = $langStringBase;
        $this->templateBase   = $templateBase;
        $this->activeLink     = $activeLink;
        $this->mauticContent  = $mauticContent;
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    protected function indexStandard($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(
            array(
                $this->permissionBase.':view',
                $this->permissionBase.':viewown',
                $this->permissionBase.':viewother',
                $this->permissionBase.':create',
                $this->permissionBase.':edit',
                $this->permissionBase.':editown',
                $this->permissionBase.':editother',
                $this->permissionBase.':delete',
                $this->permissionBase.':deleteown',
                $this->permissionBase.':deleteother',
                $this->permissionBase.':publish',
                $this->permissionBase.':publishown',
                $this->permissionBase.':publishother'
            ),
            "RETURN_ARRAY",
            null,
            true
        );

        if (!$permissions[$this->permissionBase.':viewown'] && !$permissions[$this->permissionBase.':viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $session = $this->get('session');

        //set limits
        $limit = $session->get($this->sessionBase.'.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $session->get($this->sessionBase.'.filter', ''));
        $session->set($this->sessionBase.'.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        $model = $this->getModel($this->modelName);
        $repo  = $model->getRepository();

        if (!$permissions[$this->permissionBase.':viewother']) {
            $filter['force'] = array('column' => $repo->getTableAlias().'.createdBy', 'expr' => 'eq', 'value' => $this->user->getId());
        }

        $orderBy    = $session->get($this->sessionBase.'.orderby', $repo->getTableAlias().'.name');
        $orderByDir = $session->get($this->sessionBase.'.orderbydir', 'ASC');

        $items = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($items);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : ((ceil($count / $limit)) ?: 1) ?: 1;

            $session->set($this->sessionBase.'.page', $lastPage);
            $returnUrl = $this->generateUrl($this->routeBase.'_index', array('page' => $lastPage));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage),
                    'contentTemplate' => $this->templateBase.':index',
                    'passthroughVars' => array(
                        'activeLink'    => $this->activeLink,
                        'mauticContent' => $this->mauticContent
                    )
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $session->set($this->sessionBase.'.page', $page);

        $viewParameters = array(
            'searchValue' => $search,
            'items'       => $items,
            'totalItems'  => $count,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->get('mautic.security'),
            'tmpl'        => $this->request->get('tmpl', 'index')
        );

        $delegateArgs = array(
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $this->templateBase.':list.html.php',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl($this->routeBase.'_index', array('page' => $page))
            )
        );

        // Allow inherited class to adjust
        if (method_exists($this, 'customizeViewArguments')) {
            $delegateArgs = $this->customizeViewArguments($delegateArgs, 'index');
        }

        return $this->delegateView($delegateArgs);
    }

    /**
     * Individual item's details page
     *
     * @param      $objectId
     * @param null $logObject
     * @param null $logBundle
     * @param null $listPage
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function viewStandard($objectId, $logObject= null, $logBundle =  null, $listPage = null)
    {
        $model    = $this->getModel($this->modelName);
        $entity   = $model->getEntity($objectId);
        $security = $this->get('mautic.security');

        if ($entity === null) {
            $page = $this->get('session')->get($this->sessionBase.'.page', 1);

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $this->generateUrl($this->routeBase.'_index', array('page' => $page)),
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => $this->templateBase.':index',
                    'passthroughVars' => array(
                        'activeLink'    => $this->activeLink,
                        'mauticContent' => $this->mauticContent
                    ),
                    'flashes'         => array(
                        array(
                            'type'    => 'error',
                            'msg'     => $this->langStringBase.'.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                )
            );
        } elseif (!$security->hasEntityAccess($this->permissionBase.':viewown', $this->permissionBase.':viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        // Set filters
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        // Audit log entries
        $logs = ($logObject) ? $this->getModel('core.auditLog')->getLogForObject($logObject, $objectId, $entity->getDateAdded(), 10, $logBundle) : array();

        // Generate route
        $routeVars = array(
            'objectAction' => 'view',
            'objectId'     => $entity->getId()
        );
        if ($listPage !== null) {
            $routeVars['listPage'] = $listPage;
        }
        $route = $this->generateUrl(
            $this->routeBase.'_action',
            $routeVars
        );

        $delegateArgs = array(
            'viewParameters'  => array(
                'item'        => $entity,
                'logs'        => $logs,
                'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'permissions' => $security->isGranted(
                    array(
                        $this->permissionBase.':view',
                        $this->permissionBase.':viewown',
                        $this->permissionBase.':viewother',
                        $this->permissionBase.':create',
                        $this->permissionBase.':edit',
                        $this->permissionBase.':editown',
                        $this->permissionBase.':editother',
                        $this->permissionBase.':delete',
                        $this->permissionBase.':deleteown',
                        $this->permissionBase.':deleteother',
                        $this->permissionBase.':publish',
                        $this->permissionBase.':publishown',
                        $this->permissionBase.':publishother'
                    ),
                    "RETURN_ARRAY",
                    null,
                    true
                ),
            ),
            'contentTemplate' => $this->templateBase.':details.html.php',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent,
                'route'         => $route
            )
        );

        // Allow inherited class to adjust
        if (method_exists($this, 'customizeViewArguments')) {
            $delegateArgs = $this->customizeViewArguments($delegateArgs, 'view');
        }

        return $this->delegateView($delegateArgs);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    protected function newStandard()
    {
        $model  = $this->getModel($this->modelName);
        $entity = $model->getEntity();

        if (!$this->get('mautic.security')->isGranted($this->permissionBase.':create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->get('session')->get($this->sessionBase.'.page', 1);

        $action = $this->generateUrl($this->routeBase.'_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // Allow inherited class to adjust
                    if (method_exists($this, 'beforeSaveEntity')) {
                        $valid = $this->beforeSaveEntity($entity, $form, 'new');
                    }

                    if ($valid) {
                        $model->saveEntity($entity);

                        // Allow inherited class to adjust
                        if (method_exists($this, 'afterSaveEntity')) {
                            $this->afterSaveEntity($entity, $form, 'new');
                        }

                        if (method_exists($this, 'viewAction')) {
                            $viewParameters = array('objectId' => $entity->getId(), 'objectAction' => 'view');
                            $returnUrl      = $this->generateUrl($this->routeBase.'_action', $viewParameters);
                            $template       = $this->templateBase.':view';
                        } else {
                            $viewParameters = array('page' => $page);
                            $returnUrl      = $this->generateUrl($this->routeBase.'_index', $viewParameters);
                            $template       = $this->templateBase.':index';
                        }
                    }
                }
            } else {
                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl($this->routeBase.'_index', $viewParameters);
                $template       = $this->templateBase.':index';
            }

            if ($cancelled || ($valid && !$this->isFormApplied($form))) {
                return $this->postActionRedirect(
                    array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => array(
                            'activeLink'    => $this->activeLink,
                            'mauticContent' => $this->mauticContent
                        )
                    )
                );
            } elseif ($valid && $this->isFormApplied($form)) {
                return $this->editAction($entity->getId(), true);
            }
        }

        $delegateArgs = array(
            'viewParameters'  => array(
                'tmpl'  => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity' => $entity,
                'form'  => $form->createView()
            ),
            'contentTemplate' => $this->templateBase.':form.html.php',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl(
                    $this->routeBase.'_action',
                    array(
                        'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                        'objectId'     => $entity->getId()
                    )
                )
            )
        );

        // Allow inherited class to adjust
        if (method_exists($this, 'customizeViewArguments')) {
            $delegateArgs = $this->customizeViewArguments($delegateArgs, 'new');
        }

        return $this->delegateView($delegateArgs);
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    protected function editStandard($objectId, $ignorePost = false)
    {
        $isClone = false;
        $model  = $this->getModel($this->modelName);
        if (is_object($objectId)) {
            $entity   = $objectId;
            $isClone  = true;
            $objectId = 'mautic_'.sha1(uniqid(mt_rand(), true));
        } elseif (strpos($objectId, 'mautic_') !== false) {
            $isClone = true;
            $entity = $model->getEntity();
        } else {
            $entity = $model->getEntity($objectId);
        }

        //set the page we came from
        $page = $this->get('session')->get($this->sessionBase.'.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl($this->routeBase.'_index', array('page' => $page));

        $viewParameters = array('page' => $page);
        $template       = $this->templateBase.':index';
        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => $template,
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent
            )
        );

        //form not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge(
                    $postActionVars,
                    array(
                        'flashes' => array(
                            array(
                                'type'    => 'error',
                                'msg'     => $this->langStringBase.'.error.notfound',
                                'msgVars' => array('%id%' => $objectId)
                            )
                        )
                    )
                )
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            $this->permissionBase.':editown',
            $this->permissionBase.':editother',
            $entity->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        } elseif (!$isClone && $model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $this->modelName);
        }

        $action = $this->generateUrl($this->routeBase.'_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    // Allow inherited class to adjust
                    if (method_exists($this, 'beforeSaveEntity')) {
                        $valid = $this->beforeSaveEntity($entity, $form, 'new');
                    }

                    if ($valid) {
                        $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                        // Allow inherited class to adjust
                        if (method_exists($this, 'afterSaveEntity')) {
                            $this->afterSaveEntity($entity, $form, 'new');
                        }

                        $this->addFlash(
                            'mautic.core.notice.updated',
                            array(
                                '%name%'      => $entity->getName(),
                                '%menu_link%' => $this->routeBase.'_index',
                                '%url%'       => $this->generateUrl(
                                    $this->routeBase.'_action',
                                    array(
                                        'objectAction' => 'edit',
                                        'objectId'     => $entity->getId()
                                    )
                                )
                            )
                        );

                        if (!$this->isFormApplied($form) && method_exists($this, 'viewAction')) {
                            $viewParameters = array('objectId' => $entity->getId(), 'objectAction' => 'view');
                            $returnUrl      = $this->generateUrl($this->routeBase.'_action', $viewParameters);
                            $template       = $this->templateBase.':view';
                        }
                    }
                }
            } else {
                if (!$isClone) {
                    //unlock the entity
                    $model->unlockEntity($entity);
                }

                $viewParameters = array('page' => $page);
                $returnUrl      = $this->generateUrl($this->routeBase.'_index', $viewParameters);
                $template       = $this->templateBase.':index';
            }

            if ($cancelled || ($valid && !$this->isFormApplied($form))) {
                return $this->postActionRedirect(
                    array_merge(
                        $postActionVars,
                        array(
                            'returnUrl'       => $returnUrl,
                            'viewParameters'  => $viewParameters,
                            'contentTemplate' => $template
                        )
                    )
                );
            } elseif ($valid) {
                // Rebuild the form with new action so that apply doesn't keep creating a clone
                $action = $this->generateUrl($this->routeBase.'_action', ['objectAction' => 'edit', 'objectId' => $entity->getId()]);
                $form   = $model->createForm($entity, $this->get('form.factory'), $action);
            }
        } elseif (!$isClone) {
            $model->lockEntity($entity);
        }

        $delegateArgs = array(
            'viewParameters'  => array(
                'tmpl'  => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'  => $entity,
                'form'  => $form->createView()
            ),
            'contentTemplate' => $this->templateBase.':form.html.php',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent,
                'route'         => $this->generateUrl(
                    $this->routeBase.'_action',
                    array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId()
                    )
                )
            )
        );

        // Allow inherited class to adjust
        if (method_exists($this, 'customizeViewArguments')) {
            $delegateArgs = $this->customizeViewArguments($delegateArgs, 'edit');
        }

        return $this->delegateView($delegateArgs);
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    protected function cloneStandard($objectId)
    {
        $model  = $this->getModel($this->modelName);
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->get('mautic.security')->isGranted($this->permissionBase.':create')
                || !$this->get('mautic.security')->hasEntityAccess(
                    $this->permissionBase.':viewown',
                    $this->permissionBase.':viewother',
                    $entity->getCreatedBy()
                )
            ) {
                return $this->accessDenied();
            }

            $newEntity = clone $entity;
            if (method_exists($newEntity, 'setIsPublished')) {
                $newEntity->setIsPublished(false);
            }

            // Allow inherited class to adjust
            if (method_exists($this, 'afterCloneEntity')) {
                $this->afterCloneEntity($newEntity, $entity);
            }

            return $this->editAction($newEntity, true, true);
        }

        return $this->editAction($objectId, true, true);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function deleteStandard($objectId)
    {
        $page      = $this->get('session')->get($this->sessionBase.'.page', 1);
        $returnUrl = $this->generateUrl($this->routeBase.'_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => $this->templateBase.':index',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel($this->modelName);
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => $this->langStringBase.'.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                $this->permissionBase.':deleteown',
                $this->permissionBase.':deleteother',
                $entity->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, $this->modelName);
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[]  = array(
                'type'    => 'notice',
                'msg'     => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $identifier,
                    '%id%'   => $objectId
                )
            );
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function batchDeleteStandard()
    {
        $page      = $this->get('session')->get($this->sessionBase.'.page', 1);
        $returnUrl = $this->generateUrl($this->routeBase.'_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => $this->templateBase.':index',
            'passthroughVars' => array(
                'activeLink'    => $this->activeLink,
                'mauticContent' => $this->mauticContent
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel($this->modelName);
            $ids       = json_decode($this->request->query->get('ids', ''));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => $this->langStringBase.'.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->get('mautic.security')->hasEntityAccess(
                    $this->permissionBase.':deleteown',
                    $this->permissionBase.':deleteother',
                    $entity->getCreatedBy()
                )
                ) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, $this->modelName, true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => $this->langStringBase.'.notice.batch_deleted',
                    'msgVars' => array(
                        '%count%' => count($entities)
                    )
                );
            }
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                array(
                    'flashes' => $flashes
                )
            )
        );
    }
}