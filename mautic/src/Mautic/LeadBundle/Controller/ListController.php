<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\LeadBundle\Entity\LeadList;

class ListController extends FormController
{

    /**
     * Generate's default list view
     *
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->get('mautic.security')->isGranted(array(
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:lists:viewother',
            'lead:lists:editother',
            'lead:lists:deleteother'
        ), 'RETURN_ARRAY');

        //Lists can be managed by anyone who has access to leads
        if (!$permissions['lead:leads:viewown'] || !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->get('session')->get('mautic.leadlist.limit', $this->get('mautic.factory')->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter           = array();
        $filter['string'] = $this->request->get('search', $this->get('session')->get('mautic.leadlist.filter', ''));
        $this->get('session')->set('mautic.leadlist.filter', $filter['string']);
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        if (!$permissions['lead:lists:viewother']) {
            $translator      = $this->get('translator');
            $isCommand       = $translator->trans('mautic.core.searchcommand.is');
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $global          = $translator->trans('mautic.lead.lists.searchcommand.isglobal');
            $filter["force"] = " ($isCommand:$mine or $isCommand:$global)";
        }

        $items =$this->get('mautic.factory')->getModel('lead.list')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter
            ));

        $count = count($items);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->get('session')->set('mautic.leadlist.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_leadlist_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array(
                    'page' => $lastPage,
                    'tmpl' => $tmpl
                ),
                'contentTemplate' => 'MauticLeadBundle:List:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_leadlist_index',
                    'mauticContent' => 'leadlist'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->get('session')->set('mautic.leadlist.page', $page);

        $parameters = array(
            'items'       => $items,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->get('mautic.security'),
            'tmpl'        => $tmpl,
            'currentUser' => $this->get('mautic.factory')->getUser()
        );

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticLeadBundle:List:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadlist_index',
                'route'         => $this->generateUrl('mautic_leadlist_index', array('page' => $page)),
                'mauticContent' => 'leadlist'
            )
        ));
    }

    /**
     * Generate's new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction ()
    {
        if (!$this->get('mautic.security')->isGranted('lead:leads:viewown')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $list     = new LeadList();
        $model      =$this->get('mautic.factory')->getModel('lead.list');
        //set the page we came from
        $page       = $this->get('session')->get('mautic.leadlist.page', 1);
        //set the return URL for post actions
        $returnUrl  = $this->generateUrl('mautic_leadlist_index', array('page' => $page));
        $action     = $this->generateUrl('mautic_leadlist_action', array('objectAction' => 'new'));
        //get the user form factory
        $form       = $model->createForm($list, $this->get('form.factory'),  $action);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($list);

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.lead.list.notice.created',  array(
                            '%name%' => $list->getName() . " (" . $list->getAlias() . ")",
                            '%url%'  => $this->generateUrl('mautic_leadlist_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $list->getId()
                            ))
                        ), 'flashes')
                    );
                }
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $page),
                    'contentTemplate' => 'MauticLeadBundle:List:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_leadlist_index',
                        'mauticContent' => 'leadlist'
                    )
                ));
            } elseif (!$cancelled) {
                return $this->editAction($list->getId(), true);
            }
        }

        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticLeadBundle:List:form.html.php')->get('form')
            ->setTheme($formView, 'MauticLeadBundle:FormList');

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $formView,
                'choices'         => $model->getChoiceFields(),
                'operatorOptions' => $model->getFilterExpressionFunctions()
            ),
            'contentTemplate' => 'MauticLeadBundle:List:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadlist_index',
                'route'         => $this->generateUrl('mautic_leadlist_action', array('objectAction' => 'new')),
                'mauticContent' => 'leadlist'
            )
        ));
    }

    /**
     * Generate's edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId, $ignorePost = false)
    {
        $model   =$this->get('mautic.factory')->getModel('lead.list');
        $list    = $model->getEntity($objectId);

        //set the page we came from
        $page    = $this->get('session')->get('mautic.leadlist.page', 1);

        //set the return URL
        $returnUrl  = $this->generateUrl('mautic_leadlist_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:List:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadlist_index',
                'mauticContent' => 'leadlist'
            )
        );
        //list not found
        if ($list === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type' => 'error',
                            'msg'  => 'mautic.lead.list.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            true, 'lead:lists:editother', $list->getCreatedBy()
        )) {
            return $this->accessDenied();
        } elseif ($model->isLocked($list)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $list, 'lead.list');
        }

        $action = $this->generateUrl('mautic_leadlist_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form   = $model->createForm($list, $this->get('form.factory'), $action);

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($list, $form->get('buttons')->get('save')->isClicked());

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.lead.list.notice.updated',  array(
                            '%name%' => $list->getName() . " (" . $list->getAlias() . ")",
                            '%url%'  => $this->generateUrl('mautic_leadlist_action', array(
                                'objectAction' => 'edit',
                                'objectId'     => $list->getId()
                            ))
                        ), 'flashes')
                    );
                }
            } else {
                //unlock the entity
                $model->unlockEntity($list);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'viewParameters'  => array('objectId' => $list->getId()),
                        'contentTemplate' => 'MauticLeadBundle:List:index'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($list);
        }
        $formView = $form->createView();
        $this->container->get('templating')->getEngine('MauticLeadBundle:List:form.html.php')->get('form')
            ->setTheme($formView, 'MauticLeadBundle:FormList');

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $formView,
                'choices'         => $model->getChoiceFields(),
                'operatorOptions' => $model->getFilterExpressionFunctions()
            ),
            'contentTemplate' => 'MauticLeadBundle:List:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadlist_index',
                'route'         => $action,
                'mauticContent' => 'leadlist'
            )
        ));
    }

    /**
     * Delete a list
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->get('session')->get('mautic.leadlist.page', 1);
        $returnUrl = $this->generateUrl('mautic_leadlist_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticLeadBundle:List:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_leadlist_index',
                'mauticContent' => 'lead'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  =$this->get('mautic.factory')->getModel('lead.list');
            $list = $model->getEntity($objectId);

            if ($list === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->get('mautic.security')->hasEntityAccess(
                true, 'lead:lists:deleteother', $list->getCreatedBy()
            )
            ) {
                return $this->accessDenied();
            } elseif ($model->isLocked($list)) {
                return $this->isLocked($postActionVars, $list, 'lead.list');
            }

            $model->deleteEntity($list);

            $flashes[]  = array(
                'type'    => 'notice',
                'msg'     => 'mautic.lead.list.notice.deleted',
                'msgVars' => array(
                    '%name%' => $list->getName(),
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