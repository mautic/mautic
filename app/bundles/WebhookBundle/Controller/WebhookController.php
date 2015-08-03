<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WebhookController
 */
class WebhookController extends FormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
        $model = $this->factory->getModel('webhook');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'webhook:webhooks:viewown',
            'webhook:webhooks:viewother',
            'webhook:webhooks:create',
            'webhook:webhooks:editown',
            'webhook:webhooks:editother',
            'webhook:webhooks:deleteown',
            'webhook:webhooks:deleteother',
            'webhook:webhooks:publishown',
            'webhook:webhooks:publishother'
        ), "RETURN_ARRAY");


        if (!$permissions['webhook:webhooks:viewown'] && !$permissions['webhook:webhooks:viewother']) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.webhook.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.webhook.filter', ''));
        $this->factory->getSession()->set('mautic.webhook.filter', $search);
        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['webhook:webhooks:viewother']) {
            $filter['force'][] = array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser()->getId());
        }

        $orderBy    = $this->factory->getSession()->get('mautic.webhook.orderby', 'e.title');
        $orderByDir = $this->factory->getSession()->get('mautic.webhook.orderbydir', 'DESC');

        $webhooks = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($webhooks);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->factory->getSession()->set('mautic.webhook.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_webhook_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('webhook' => $lastPage),
                'contentTemplate' => 'MauticWebhookBundle:Webhook:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_webhook_index',
                    'mauticContent' => 'webhook'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.webhook.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $webhooks,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticWebhookBundle:Webhook:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_webhook_index',
                'mauticContent'  => 'page',
                'route'          => $this->generateUrl('mautic_webhook_index', array('page' => $page))
            )
        ));
    }

    /**
     * Generates new webhook and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function newAction()
    {
        /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
        $model   = $this->factory->getModel('webhook');
        $method  = $this->request->getMethod();
        $entity  = $model->getEntity();
        $session = $this->factory->getSession();

        // get the list of events from the model
        $availableEvents  = $model->getAvailableEvents();

        if (!$this->factory->getSecurity()->isGranted('webhook:webhooks:create')) {
            return $this->accessDenied();
        }

        //set the page we came from
        $page = $this->factory->getSession()->get('mautic.webhook.page', 1);
        $action = $this->generateUrl('mautic_webhook_action', array('objectAction' => 'new'));

        //create the form
        $form = $model->createForm($entity, $this->get('form.factory'), $action, array(
            'eventList' => $availableEvents
        ));

        ///Check for a submitted form and process it
        if ($method == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity);

                    $this->addFlash('mautic.core.notice.created', array(
                        '%name%'      => $entity->getTitle(),
                        '%menu_link%' => 'mautic_webhook_index',
                        '%url%'       => $this->generateUrl('mautic_webhook_action', array(
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId()
                        ))
                    ));

                    if ($form->get('buttons')->get('save')->isClicked()) {
                        $viewParameters = array(
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId()
                        );
                        $returnUrl      = $this->generateUrl('mautic_webhook_action', $viewParameters);
                    } else {
                        //return edit view so that all the session stuff is loaded
                        return $this->editAction($entity->getId(), true);
                    }
                }
            } else {
                $viewParameters  = array('page' => $page);
                $returnUrl = $this->generateUrl('mautic_webhook_index', $viewParameters);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                return $this->postActionRedirect(array(
                    'returnUrl'       => $returnUrl,
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticWebhookBundle:Webhook:index',
                    'passthroughVars' => array(
                        'activeLink'    => 'mautic_webhook_index',
                        'mauticContent' => 'page'
                    )
                ));
            }

        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $form->createView(),
                'activePage'  => $entity,
                'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'activeForm'  => $entity,
            ),
            'contentTemplate' => 'MauticWebhookBundle:Webhook:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_webhook_index',
                'mauticContent' => 'page',
                'route'         => $this->generateUrl('mautic_webhook_action', array(
                    'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                    'objectId'     => $entity->getId())
                )
            )
        ));
    }
}