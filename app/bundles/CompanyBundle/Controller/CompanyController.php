<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CompanyBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CompanyBundle\Entity\Company;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CompanyController
 */
class CompanyController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|Response
     */
    public function indexAction($page = 1)
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(
            array(
                'company:companies:view',
                'company:companies:create',
                'company:companies:edit',
                'company:companies:delete'
            ),
            "RETURN_ARRAY"
        );

        if (!$permissions['company:companies:view']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get(
            'mautic.company.limit',
            $this->factory->getParameter('default_pagelimit')
        );
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.company.filter', ''));
        $this->factory->getSession()->set('mautic.company.filter', $search);

        $filter     = array('string' => $search, 'force' => array());
        $orderBy    = $this->factory->getSession()->get('mautic.company.orderby', 'comp.name');

        $companies = $this->factory->getModel('company')->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy
            )
        );

        $count = count($companies);
        if ($count && $count < ($start + 1)) {
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->factory->getSession()->set('mautic.company.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_company_index', array('page' => $lastPage));

            return $this->postActionRedirect(
                array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => array('page' => $lastPage),
                    'contentTemplate' => 'MauticCompanyBundle:Company:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_company_index',
                        'mauticContent' => 'company'
                    )
                )
            );
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.company.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        $model = $this->getModel('company');
        $companyIds    = array_keys($companies->getIterator()->getArrayCopy());
        $leadCounts = (!empty($companyIds)) ? $model->getRepository()->getLeadCount($companyIds) : array();
        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'searchValue' => $search,
                    'leadCounts'  => $leadCounts,
                    'items'       => $companies,
                    'page'        => $page,
                    'limit'       => $limit,
                    'permissions' => $permissions,
                    'tmpl'        => $tmpl
                ),
                'contentTemplate' => 'MauticCompanyBundle:Company:list.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'route'         => $this->generateUrl('mautic_company_index', array('page' => $page))
                )
            )
        );
    }

    /**
     * Generates new form and processes post data
     *
     * @param  \Mautic\CompanyBundle\Entity\Company $entity
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function newAction($entity = null)
    {
        $model = $this->getModel('company');

        if (!($entity instanceof Company)) {
            /** @var \Mautic\CompanyBundle\Entity\Company $entity */
            $entity = $model->getEntity();
        }

        if (!$this->factory->getSecurity()->isGranted('company:companies:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.company.page', 1);

        $action         = $this->generateUrl('mautic_company_action', array('objectAction' => 'new'));
        $form           = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            array()
        );
        $viewParameters = array('page' => $page);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash(
                        'mautic.core.notice.created',
                        array(
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_company_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_company_action',
                                array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                )
                            )
                        )
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'MauticCompanyBundle:Company:index';
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                $template  = 'MauticCompanyBundle:Company:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(
                    array(
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => $template,
                        'passthroughVars' => array(
                            'activeLink'    => '#mautic_company_index',
                            'mauticContent' => 'company'
                        )
                    )
                );
            }
        }

        $themes = array('MauticCompanyBundle:FormTheme\Action');

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'  => $entity,
                    'form'    => $this->setFormTheme($form, 'MauticCompanyBundle:Company:form.html.php', $themes)
                ),
                'contentTemplate' => 'MauticCompanyBundle:Company:form.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
                        array(
                            'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        $model  = $this->getModel('company');
        $entity = $model->getEntity($objectId);

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.company.page', 1);

        $viewParameters = array('page' => $page);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_company_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticCompanyBundle:Company:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company'
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
                                'msg'     => 'mautic.company.error.notfound',
                                'msgVars' => array('%id%' => $objectId)
                            )
                        )
                    )
                )
            );
        } elseif (!$this->factory->getSecurity()->isGranted('company:companies:edit')) {
            return $this->accessDenied();
        } elseif ($model->isLocked($entity)) {
            //deny access if the entity is locked
            return $this->isLocked($postActionVars, $entity, 'company');
        }

        $action  = $this->generateUrl('mautic_company_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $form    = $model->createForm(
            $entity,
            $this->get('form.factory'),
            $action,
            array()
        );

        ///Check for a submitted form and process it
        if (!$ignorePost && $this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    $this->addFlash(
                        'mautic.core.notice.updated',
                        array(
                            '%name%'      => $entity->getName(),
                            '%menu_link%' => 'mautic_company_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_company_action',
                                array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                )
                            )
                        )
                    );

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                        $template  = 'MauticCompanyBundle:Company:index';
                    }
                }
            } else {
                //unlock the entity
                $model->unlockEntity($entity);

                $returnUrl = $this->generateUrl('mautic_company_index', $viewParameters);
                $template  = 'MauticCompanyBundle:Company:index';
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
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
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        $themes = array('MauticCompanyBundle:FormTheme\Action');

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'tmpl'    => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                    'entity'  => $entity,
                    'form'    => $this->setFormTheme($form, 'MauticCompanyBundle:Company:form.html.php', $themes)
                ),
                'contentTemplate' => 'MauticCompanyBundle:Company:form.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_company_index',
                    'mauticContent' => 'company',
                    'route'         => $this->generateUrl(
                        'mautic_company_action',
                        array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        )
                    )
                )
            )
        );
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        $model  = $this->getModel('company');
        $entity = $model->getEntity($objectId);

        if ($entity != null) {
            if (!$this->factory->getSecurity()->isGranted('company:companies:create')) {
                return $this->accessDenied();
            }

            $entity = clone $entity;
        }

        return $this->newAction($entity);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $page      = $this->factory->getSession()->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticCompanyBundle:Company:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model  = $this->getModel('company');
            $entity = $model->getEntity($objectId);

            if ($entity === null) {
                $flashes[] = array(
                    'type'    => 'error',
                    'msg'     => 'mautic.company.error.notfound',
                    'msgVars' => array('%id%' => $objectId)
                );
            } elseif (!$this->factory->getSecurity()->isGranted('company:companies:delete')) {
                return $this->accessDenied();
            } elseif ($model->isLocked($entity)) {
                return $this->isLocked($postActionVars, $entity, 'company');
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
    public function batchDeleteAction()
    {
        $page      = $this->factory->getSession()->get('mautic.company.page', 1);
        $returnUrl = $this->generateUrl('mautic_company_index', array('page' => $page));
        $flashes   = array();

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'MauticCompanyBundle:Company:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_company_index',
                'mauticContent' => 'company'
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $model     = $this->getModel('company');
            $ids       = json_decode($this->request->query->get('ids', '{}'));
            $deleteIds = array();

            // Loop over the IDs to perform access checks pre-delete
            foreach ($ids as $objectId) {
                $entity = $model->getEntity($objectId);

                if ($entity === null) {
                    $flashes[] = array(
                        'type'    => 'error',
                        'msg'     => 'mautic.company.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    );
                } elseif (!$this->factory->getSecurity()->isGranted('company:companies:delete')) {
                    $flashes[] = $this->accessDenied(true);
                } elseif ($model->isLocked($entity)) {
                    $flashes[] = $this->isLocked($postActionVars, $entity, 'company', true);
                } else {
                    $deleteIds[] = $objectId;
                }
            }

            // Delete everything we are able to
            if (!empty($deleteIds)) {
                $entities = $model->deleteEntities($deleteIds);

                $flashes[] = array(
                    'type'    => 'notice',
                    'msg'     => 'mautic.company.notice.batch_deleted',
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
