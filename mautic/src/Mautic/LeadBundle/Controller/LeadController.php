<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//@todo - write merge action
//@todo - write export action

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class LeadController extends FormController
{

    /**
     * @param int    $page
     * @param string $view
     * @param bool   $activeLead
     * @param bool   $form
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1 , $view = 'list', $activeLead = false, $form = false)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(array(
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:leads:create',
            'lead:leads:editown',
            'lead:leads:editother',
            'lead:leads:deleteown',
            'lead:leads:deleteother'
        ), "RETURN_ARRAY");

        if (!$permissions['lead:leads:viewown'] || !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.lead.limit', $this->container->getParameter('mautic.default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search     = $this->request->get('search', $this->get('session')->get('mautic.lead.filter', ''));
        $this->get('session')->set('mautic.lead.filter', $search);

        //do some default filtering
        $filter      = array('string' => $search, 'force' => '');
        $translator  = $this->container->get('translator');
        $isCommand   = $translator->trans('mautic.core.searchcommand.is');
        $anonymous   = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        $listCommand = $translator->trans('mautic.lead.lead.searchcommand.list');

        if (strpos($search, "$isCommand:$anonymous") === false && strpos($search, "$listCommand:") === false) {
            //remove anonymous leads unless requested to prevent clutter
            $filter['force'] .= " !$isCommand:$anonymous";
        }
        if (!$permissions['lead:leads:viewother']) {
            $mine             = $translator->trans('mautic.core.searchcommand.ismine');
            $filter['force'] .= " $isCommand:$mine";
        }

        $leads = $this->get('mautic.factory')->getModel('lead.lead')->getEntities(
            array(
                'start'         => $start,
                'limit'         => $limit,
                'filter'        => $filter,
                'orderByDir'    => "DESC",
                'getTotalCount' => true
            ));

        $count = $leads['totalCount'];
        unset($leads['totalCount']);

        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.lead.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_lead_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.lead.page', $page);

        //get active lead
        if (empty($activeLead) && $count) {
            $activeLead = $leads[0];
        }

        $tmpl  = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $listArgs = array();
        if (!$this->get('mautic.security')->isGranted('lead:lists:viewother')) {
            $listArgs["filter"]["force"] = " $isCommand:$mine";
        }

        $lists = $this->get('mautic.factory')->getModel('lead.list')->getSmartLists();
        $factory = $this->get('mautic.factory');
        $parameters = array(
            'searchValue' => $search,
            'items'       => $leads,
            'page'        => $page,
            'limit'       => $limit,
            'totalCount'  => $count,
            'permissions' => $permissions,
            'lead'        => $activeLead,
            'form'        => $form,
            'tmpl'        => $tmpl,
            'lists'       => $lists,
            'security'    => $this->get('mautic.security'),
            'dateFormats' => array(
                'datetime' => $factory->getParam('date_format_full'),
                'date'     => $factory->getParam('date_format_dateonly'),
                'time'     => $factory->getParam('date_format_timeonly'),
            )
        );

        $vars = array('activeLink' => '#mautic_lead_index', 'mauticContent'   => 'lead');

        if ($tmpl == "index") {
            switch ($view) {
                case 'list':
                    $template      = 'MauticLeadBundle:Lead:lead.html.php';
                    $vars['route'] = $this->generateUrl('mautic_lead_index', array('page' => $page));
                    break;
                case 'view':
                    $template      = 'MauticLeadBundle:Lead:lead.html.php';
                    $vars['route'] = (!empty($activeLead)) ?
                        $this->generateUrl('mautic_lead_action', array(
                                'objectAction' => 'view',
                                'objectId'     => $activeLead->getId())
                        ) :
                        $this->generateUrl('mautic_lead_index', array('page' => $page)
                        );
                    break;
                case 'edit':
                case 'new':
                    $template      = 'MauticLeadBundle:Lead:form.html.php';
                    $vars['route'] = $this->generateUrl('mautic_lead_action', array(
                            'objectAction' => $view,
                            'objectId'     => $activeLead->getId())
                    );
                    break;
            }
        } elseif ($tmpl == 'lead') {
            $template       = 'MauticLeadBundle:Lead:lead.html.php';
            $vars['route']  = $this->generateUrl('mautic_lead_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $activeLead->getId())
            );
            $vars['target'] = '.lead-details-inner-wrapper';

        } else {
            $template      = 'MauticLeadBundle:Lead:list.html.php';
            $vars['route'] = $this->generateUrl('mautic_lead_index', array('page' => $page));
            if ($tmpl == 'list') {
                $vars['target'] = '.leads';
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => $template,
            'passthroughVars' => $vars
        ));
    }

    /**
     * Loads a specific lead into the detailed panel
     *
     * @param $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewAction($objectId)
    {
        $activeLead  = $this->get('mautic.factory')->getModel('lead.lead')->getEntity($objectId);
        //set the page we came from
        $page    = $this->get('session')->get('mautic.lead.page', 1);

        if ($activeLead === null) {
            //set the return URL
            $returnUrl  = $this->generateUrl('mautic_lead_index', array('page' => $page));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticLeadBundle:Lead:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_lead_index',
                    'mauticContent' => 'lead'
                ),
                'flashes'         =>array(
                    array(
                        'type' => 'error',
                        'msg'  => 'mautic.lead.lead.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        }

        if (!$this->get('mautic.security')->hasEntityAccess(
            'lead:leads:viewown', 'lead:leads:viewother', $activeLead->getOwner()
        )) {
            return $this->accessDenied();
        }

        return $this->indexAction($page, 'view', $activeLead);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        $model   = $this->get('mautic.factory')->getModel('lead.lead');
        $lead    = $model->getEntity();

        if (!$this->get('mautic.security')->isGranted('lead:leads:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page    = $this->get('session')->get('mautic.lead.page', 1);

        $action = $this->generateUrl('mautic_lead_action', array('objectAction' => 'new'));
        $form   = $model->createForm($lead, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //get custom field values
                    $data = $this->request->request->get('lead');

                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $name = $f->getName();
                        if (strpos($name, 'field_') === 0) {
                            $data[$name] = $f->getData();
                        }
                    }

                    $model->setFieldValues($lead, $data);

                    //form is valid so process the data
                    $model->saveEntity($lead);

                    $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.lead.list.notice.created',  array(
                            '%name%' => $identifier,
                            '%url%'  => $this->generateUrl('mautic_lead_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $lead->getId()
                            ))
                        ), 'flashes')
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = array(
                            'objectAction' => 'view',
                            'objectId'     => $lead->getId()
                        );
                        $returnUrl      = $this->generateUrl('mautic_lead_action', $viewParameters);
                        $template       = 'MauticLeadBundle:Lead:view';
                    } else {
                        return $this->editAction($lead->getId(), true);
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_lead_index', $viewParameters);
                $template  = 'MauticLeadBundle:Lead:index';
            }

            if ($cancelled || $valid) { //cancelled or success
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_lead_index',
                        'mauticContent' => 'lead'
                    )
                ));
            }
        } else {
            //set the default owner to the currently logged in user
            $currentUser = $this->get('security.context')->getToken()->getUser();
            $form->get('owner')->setData($currentUser);
            $userName = $currentUser->getName();
            $form->get('owner_lookup')->setData($userName);
        }

        return $this->indexAction($page, 'new', $lead, $form->createView());
    }

    /**
     * Generates edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model   = $this->get('mautic.factory')->getModel('lead.lead');
        $lead    = $model->getEntity($objectId);

        //set the page we came from
        $page    = $this->get('session')->get('mautic.lead.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_lead_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead'
            )
        );
        //lead not found
        if ($lead === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.lead.lead.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
        'lead:leads:editown', 'lead:leads:editother', $lead->getOwner()
        )) {
            return $this->accessDenied();
        } elseif ($model->isLocked($lead)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $lead, 'lead', "getPrimaryIdentifier");
        }

        $action = $this->generateUrl('mautic_lead_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($lead, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $data = $this->request->request->get('lead');

                    //pull the data from the form in order to apply the form's formatting
                    foreach ($form as $f) {
                        $name = $f->getName();
                        if (strpos($name, 'field_') === 0) {
                            $data[$name] = $f->getData();
                        }
                    }

                    $model->setFieldValues($lead, $data);
                    //form is valid so process the data
                    $model->saveEntity($lead, $form->get('buttons')->get('save')->isClicked());

                    $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());
                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.lead.list.notice.created',  array(
                            '%name%' => $identifier,
                            '%url%'  => $this->generateUrl('mautic_lead_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $lead->getId()
                            ))
                        ), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $model->unlockEntity($lead);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $returnUrl = $this->generateUrl('mautic_lead_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $lead->getId()
                ));

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => array('objectId' => $lead->getId()),
                        'contentTemplate' => 'MauticLeadBundle:Lead:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($lead);

            $owner = $lead->getOwner();
            if (!empty($owner)) {
                $userName = $owner->getName();
                $form->get('owner_lookup')->setData($userName);
            }
        }

        return $this->indexAction($page, 'edit', $lead, $form->createView());
    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId) {
        $page        = $this->get('session')->get('mautic.lead.page', 1);
        $returnUrl   = $this->generateUrl('mautic_lead_index', array('page' => $page));
        $flashes     = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:Lead:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_lead_index',
                'mauticContent' => 'lead'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->get('mautic.factory')->getModel('lead.lead');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                'lead:leads:deleteown', 'lead:leads:deleteother', $entity->getOwner()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity);
            }

            $model->deleteEntity($entity);

            $identifier = $this->get('translator')->trans($entity->getPrimaryIdentifier());
            $flashes[] = array(
                'type' => 'notice',
                'msg'  => 'mautic.lead.lead.notice.deleted',
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
}