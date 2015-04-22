<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
        /** @var \Mautic\LeadBundle\Model\ListModel $model */
        $model = $this->factory->getModel('lead.list');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'lead:leads:viewown',
            'lead:leads:viewother',
            'lead:lists:viewother',
            'lead:lists:editother',
            'lead:lists:deleteother'
        ), 'RETURN_ARRAY');

        //Lists can be managed by anyone who has access to leads
        if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.leadlist.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $filter           = array();
        $filter['string'] = $this->request->get('search', $this->factory->getSession()->get('mautic.leadlist.filter', ''));
        $this->factory->getSession()->set('mautic.leadlist.filter', $filter['string']);
        $tmpl       = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        if (!$permissions['lead:lists:viewother']) {
            $translator      = $this->get('translator');
            $mine            = $translator->trans('mautic.core.searchcommand.ismine');
            $global          = $translator->trans('mautic.lead.list.searchcommand.isglobal');
            $filter["force"] = " ($mine or $global)";
        }

        $items = $model->getEntities(
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
                $lastPage = (floor($limit / $count)) ?: 1;
            }
            $this->factory->getSession()->set('mautic.leadlist.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_leadlist_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array(
                    'page'        => $lastPage,
                    'tmpl'        => $tmpl
                ),
                'contentTemplate' => 'MauticLeadBundle:List:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_leadlist_index',
                    'mauticContent' => 'leadlist'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.leadlist.page', $page);

        $listIds    = array_keys($items->getIterator()->getArrayCopy());
        $leadCounts = (!empty($listIds)) ? $model->getRepository()->getLeadCount($listIds) : array();

        $parameters = array(
            'items'       => $items,
            'leadCounts'  => $leadCounts,
            'page'        => $page,
            'limit'       => $limit,
            'permissions' => $permissions,
            'security'    => $this->factory->getSecurity(),
            'tmpl'        => $tmpl,
            'currentUser' => $this->factory->getUser(),
            'searchValue' => $filter['string']
        );

        return $this->delegateView(array(
            'viewParameters'  => $parameters,
            'contentTemplate' => 'MauticLeadBundle:List:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_leadlist_index',
                'route'          => $this->generateUrl('mautic_leadlist_index', array('page' => $page)),
                'mauticContent'  => 'leadlist'
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
        if (!$this->factory->getSecurity()->isGranted('lead:leads:viewown')) {
            return $this->accessDenied();
        }

        //retrieve the entity
        $list     = new LeadList();
        $model      =$this->factory->getModel('lead.list');
        //set the page we came from
        $page       = $this->factory->getSession()->get('mautic.leadlist.page', 1);
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

                    $this->addFlash('mautic.core.notice.created',  array(
                        '%name%'      => $list->getName() . " (" . $list->getAlias() . ")",
                        '%menu_link%' => 'mautic_leadlist_index',
                        '%url%'       => $this->generateUrl('mautic_leadlist_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $list->getId()
                        ))
                    ));
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
            } elseif ($valid && !$cancelled) {
                return $this->editAction($list->getId(), true);
            }
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $form->createView(),
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
        $model   = $this->factory->getModel('lead.list');
        $list    = $model->getEntity($objectId);

        //set the page we came from
        $page    = $this->factory->getSession()->get('mautic.leadlist.page', 1);

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
        } elseif (!$this->factory->getSecurity()->hasEntityAccess(
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

                    $this->addFlash('mautic.core.notice.updated',  array(
                        '%name%'      => $list->getName() . " (" . $list->getAlias() . ")",
                        '%menu_link%' => 'mautic_leadlist_index',
                        '%url%'       => $this->generateUrl('mautic_leadlist_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $list->getId()
                        ))
                    ));
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

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'            => $form->createView(),
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
        $page      = $this->factory->getSession()->get('mautic.leadlist.page', 1);
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
            $model  =$this->factory->getModel('lead.list');
            $list = $model->getEntity($objectId);

            if ($list === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
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
                'msg'     => 'mautic.core.notice.deleted',
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

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction() {
        $page        = $this->factory->getSession()->get('mautic.leadlist.page', 1);
        $returnUrl   = $this->generateUrl('mautic_leadlist_index', array('page' => $page));
        $flashes     = array();

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
            $model     = $this->factory->getModel('lead.list');
            $ids       = json_decode($this->request->query->get('ids', array()));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.lead.list.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                    true, 'lead:lists:deleteother', $entity->getCreatedBy()
                )) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'lead.list', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type' => 'notice',
                    'msg'  => 'mautic.lead.list.notice.batch_deleted',
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
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function removeLeadAction($objectId)
    {
        return $this->changeList($objectId, 'remove');
    }

    /**
     * @param $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addLeadAction($objectId)
    {
        return $this->changeList($objectId, 'add');
    }

    /**
     * @param $listId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function changeList($listId, $action) {
        $page        = $this->factory->getSession()->get('mautic.lead.page', 1);
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

        $leadId = $this->request->get('leadId');
        if (!empty($leadId) && $this->request->getMethod() == 'POST') {
            /** @var \Mautic\LeadBundle\Model\ListModel $model */
            $model  = $this->factory->getModel('lead.list');
            /** @var \Mautic\LeadBundle\Entity\LeadList $model */
            $list   = $model->getEntity($listId);

            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.lead.error.notfound',
                    'msgVars' => array('%id%' => $listId)
                );
            } elseif (!$this->factory->getSecurity()->hasEntityAccess(
                'lead:leads:editown', 'lead:leads:editother', $lead->getOwner()
            )) {
                return $this->accessDenied();
            } elseif ($list === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.lead.list.error.notfound',
                    'msgVars' => array('%id%' => $list->getId())
                );
            } elseif (!$list->isGlobal() && !$this->factory->getSecurity()->hasEntityAccess(
                true, 'lead:lists:viewother', $list->getCreatedBy()
            )) {
                return $this->accessDenied();
            } elseif ($model->isLocked($lead)) {
                return $this->isLocked($postActionVars, $lead, 'lead');
            } else {
                $function = ($action == 'remove') ? 'removeLead' : 'addLead';
                $model->$function($lead, $list, true);

                $identifier = $this->get('translator')->trans($lead->getPrimaryIdentifier());
                $flashes[]  = array(
                    'type'    => 'notice',
                    'msg'     => ($action == 'remove') ? 'mautic.lead.lead.notice.removedfromlists' :
                        'mautic.lead.lead.notice.addedtolists',
                    'msgVars' => array(
                        '%name%' => $identifier,
                        '%id%'   => $leadId,
                        '%list%' => $list->getName(),
                        '%url%'  => $this->generateUrl('mautic_lead_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $leadId
                        ))
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
