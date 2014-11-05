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
use Mautic\MapperBundle\Helper\IntegrationsHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

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

        $applicationObject = IntegrationsHelper::getApplication($this->factory, $application);

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
                'applicationObject' => $applicationObject,
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
                'route'          => $this->generateUrl('mautic_mapper_client_index', $viewParams),
                'replaceContent' => ($tmpl == 'list') ? 'true': 'false'
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

        $applicationObject = IntegrationsHelper::getApplication($this->factory, $application);

        //set the page we came from
        $page   = $session->get('mautic.mapper.page', 1);
        $action = $this->generateUrl('mautic_mapper_client_action', array(
            'objectAction' => 'new',
            'application'       => $application
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
                        $this->get('translator')->trans('mautic.mapper.notice.created', array(
                            '%name%' => $entity->getTitle(),
                            '%url%'          => $this->generateUrl('mautic_mapper_client_action', array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId(),
                                    'application'       => $application
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
                'application' => $application,
                'applicationObject' => $applicationObject
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

    }

    /**
     * Deletes the entity
     *
     * @param         $objectId
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($application, $objectId)
    {

    }
}