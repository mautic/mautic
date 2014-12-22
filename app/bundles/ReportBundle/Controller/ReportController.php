<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\ReportBundle\Generator\ReportGenerator;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\HttpFoundation;

/**
 * Class ReportController
 */
class ReportController extends FormController
{

    /**
     * @param int $page
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
	    /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model = $this->factory->getModel('report');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'report:reports:viewown',
            'report:reports:viewother',
            'report:reports:create',
            'report:reports:editown',
            'report:reports:editother',
            'report:reports:deleteown',
            'report:reports:deleteother',
            'report:reports:publishown',
            'report:reports:publishother'
        ), "RETURN_ARRAY");

        if (!$permissions['report:reports:viewown'] && !$permissions['report:reports:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.report.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.report.filter', ''));
        $this->factory->getSession()->set('mautic.report.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['report:reports:viewother']) {
            $filter['force'][] = array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $orderBy    = $this->factory->getSession()->get('mautic.report.orderby', 'p.title');
        $orderByDir = $this->factory->getSession()->get('mautic.report.orderbydir', 'DESC');

        $reports = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($reports);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (floor($limit / $count)) ?: 1;
            $this->factory->getSession()->set('mautic.report.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_report_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticReportBundle:Report:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.report.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $reports,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticReportBundle:Report:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_report_index',
                'mauticContent'  => 'report',
                'route'          => $this->generateUrl('mautic_report_index', array('page' => $page))
            )
        ));
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function cloneAction($objectId)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->factory->getModel('report');
        $entity  = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('report:reports:create') ||
                !$this->factory->getSecurity()->hasEntityAccess(
                    'report:reports:viewown', 'report:reports:viewother', $entity->getCreatedBy()
                )
            ) {
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
     * @param $objectId
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.report.page', 1);
        $returnUrl = $this->generateUrl('mautic_report_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            /* @type \Mautic\ReportBundle\Model\ReportModel $model */
            $model  = $this->factory->getModel('report');
            $entity = $model->getEntity($objectId);

            $this->checkEntityAccess($entity, $objectId, array('report:reports:deleteown', 'report:reports:deleteother'), $model, 'report');

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getTitle());
            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.core.notice.deleted',
                'msgVars' => array(
                    '%name%' => $identifier,
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
    public function batchDeleteAction() {
        $page        = $this->factory->getSession()->get('mautic.report.page', 1);
        $returnUrl   = $this->generateUrl('mautic_report_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->factory->getModel('report');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.report.report.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                    'report:reports:deleteown', 'report:reports:deleteother', $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'report', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.report.report.notice.batch_deleted',
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
     * Generates edit form and processes post data
     *
     * @param int  $objectId   Item ID
     * @param bool $ignorePost Flag to ignore POST data
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->factory->getModel('report');
        $entity  = $model->getEntity($objectId);
        $session = $this->factory->getSession();
        $page    = $session->get('mautic.report.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_report_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticReportBundle:Report:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_report_index',
                'mauticContent' => 'report'
            )
        );

        //not found
        $this->checkEntityAccess($entity, $objectId, array('report:reports:viewown', 'report:reports:viewother'), $model, 'report');

        //Create the form
        $action = $this->generateUrl('mautic_report_action', array('objectAction' => 'edit', 'objectId' => $objectId));
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
                        $this->get('translator')->trans('mautic.core.notice.updated', array(
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_report_index',
                            '%url%'       => $this->generateUrl('mautic_report_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );

                    $returnUrl = $this->generateUrl('mautic_report_action', array(
                        'objectAction' => 'view',
                        'reportId'     => $entity->getId()
                    ));
                    $viewParams = array('reportId' => $entity->getId());
                    $template = 'MauticReportBundle:Report:view';
                } else {
                    $this->request->getSession()->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('mautic.core.error.not.valid', array(), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_report_index', array('page' => $page));
                $viewParams = array('report' => $page);
                $template  = 'MauticReportBundle:Report:index';
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
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'report'      => $entity,
                'form'        => $this->setFormTheme($form, 'MauticReportBundle:Report:form.html.php', 'MauticReportBundle:FormTheme\Report'),
            ),
            'contentTemplate' => 'MauticReportBundle:Report:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
                'route'         => $this->generateUrl('mautic_report_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * Generates new form and processes post data
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function newAction()
    {
        if (!$this->factory->getSecurity()->isGranted('report:reports:create')) {
            return $this->accessDenied();
        }

        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model   = $this->factory->getModel('report');
        $entity  = $model->getEntity();
        $session = $this->factory->getSession();
        $page    = $session->get('mautic.report.page', 1);

        $action = $this->generateUrl('mautic_report_action', array('objectAction' => 'new'));
        $form   = $model->createForm($entity, $this->get('form.factory'), $action);

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
                            '%menu_link%' => 'mautic_report_index',
                            '%url%'       => $this->generateUrl('mautic_report_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $entity->getId()
                            ))
                        ), 'flashes')
                    );

                    if (!$form->get('buttons')->get('save')->isClicked()) {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }

                    $viewParameters = array(
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId()
                    );
                    $returnUrl = $this->generateUrl('mautic_report_action', $viewParameters);
                    $template  = 'MauticReportBundle:Report:index';
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_report_index', $viewParameters);
                $template  = 'MauticReportBundle:Report:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_asset_index',
                        'mauticContent' => 'asset'
                    )
                ));
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'report'      => $entity,
                'form'        => $this->setFormTheme($form, 'MauticReportBundle:Report:form.html.php', 'MauticReportBundle:FormTheme\Report')
            ),
            'contentTemplate' => 'MauticReportBundle:Report:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
                'route'         => $this->generateUrl('mautic_report_action', array(
                    'objectAction' => 'new'
                ))
            )
        ));
    }

    /**
     * Shows a report
     *
     * @param int $reportId Report ID
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\Response
     */
    public function viewAction($reportId)
    {
        /* @type \Mautic\ReportBundle\Model\ReportModel $model */
        $model    = $this->factory->getModel('report');
        $entity   = $model->getEntity($reportId);
        $security = $this->factory->getSecurity();

        //set the page we came from
        $page       = $this->factory->getSession()->get('mautic.report.page', 1);
        $reportPage = $this->request->get('reportPage', 1);

        if ($entity === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $this->generateUrl('mautic_report_index', array('page' => $page)),
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticReportBundle:Report:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_report_index',
                    'mauticContent' => 'report'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.report.report.error.notfound',
                        'msgVars' => array('%id%' => $reportId)
                    )
                )
            ));
        } elseif (!$security->hasEntityAccess('report:reports:viewown', 'report:reports:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $orderBy    = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.orderby', '');
        $orderByDir = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.orderbydir', 'ASC');

        $limit = $this->factory->getSession()->get('mautic.report.' . $entity->getId() . '.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($reportPage === 1) ? 0 : (($reportPage-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $reportGenerator = new ReportGenerator($this->factory->getSecurityContext(), $this->container->get('form.factory'), $entity);

        // Build the options array to pass into the query
        $options = array(
            'start'      => $start,
            'limit'      => $limit,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
            'dispatcher' => $this->factory->getDispatcher()
        );

        $query           = $reportGenerator->getQuery($options);
        $result          = $query->getConnection()->executeQuery((string) $query)->fetchAll();
        $contentTemplate = $reportGenerator->getContentTemplate();

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.report.' . $entity->getId() . '.page', $reportPage);

        $event = new ReportGraphEvent($entity);
        $this->factory->getDispatcher()->dispatch(ReportEvents::REPORT_ON_GRAPH_GENERATE, $event);
        $graphs = $event->getGraphs();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'result'     => $result,
                'report'     => $entity,
                'reportPage' => $page,
                'graphs'     => $graphs,
                'tmpl'       => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'limit'      => $limit,
                'security'   => $security,
                'permissions'   => $security->isGranted(array(
                    'report:reports:viewown',
                    'report:reports:viewother',
                    'report:reports:create',
                    'report:reports:editown',
                    'report:reports:editother',
                    'report:reports:deleteown',
                    'report:reports:deleteother'
                ), "RETURN_ARRAY"),
            ),
            'contentTemplate' => $contentTemplate,
            'passthroughVars' => array(
                'activeLink'    => '#mautic_report_index',
                'mauticContent' => 'report',
                'route'         => $this->generateUrl('mautic_report_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                    'reportPage'   => $reportPage
                ))
            )
        ));
    }

    /**
     * Checks access to an entity
     *
     * @param object                               $entity
     * @param int                                  $objectId
     * @param array                                $permissions
     * @param \Mautic\CoreBundle\Model\CommonModel $model
     * @param string                               $modelName
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|void
     */
    private function checkEntityAccess($entity, $objectId, array $permissions, $model, $modelName)
    {
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.report.report.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }  elseif (!$this->factory->getSecurity()->hasEntityAccess($permissions[0], $permissions[1], $entity->getCreatedBy())) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, $modelName);
        }
    }
}
