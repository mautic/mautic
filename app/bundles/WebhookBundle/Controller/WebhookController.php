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
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation;

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
            'availableEvents' => $availableEvents
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

                    // update the audit log
                    $this->updateAuditLog($entity, 'create');

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
                        'mauticContent' => 'webhook'
                    )
                ));
            }

        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'form'        => $form->createView(),
                'webhook'     => $entity,
                'tmpl'        => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
            ),
            'contentTemplate' => 'MauticWebhookBundle:Webhook:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_webhook_index',
                'mauticContent' => 'webhook',
                'route'         => $this->generateUrl('mautic_webhook_action', array(
                    'objectAction' => (!empty($valid) ? 'edit' : 'new'), //valid means a new form was applied
                    'objectId'     => $entity->getId())
                )
            )
        ));
    }

    /**
     * Generates an edit form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($objectId)
    {
        $action  = $this->generateUrl('mautic_webhook_action', array('objectAction' => 'edit', 'objectId' => $objectId));

        /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
        $model   = $this->factory->getModel('webhook.webhook');

        $entity  = $model->getEntity($objectId);
        $method  = $this->request->getMethod();
        $session = $this->factory->getSession();

        // Set the page we came from
        $page   = $session->get('mautic.webhook.page', 1);

        //set the return URL
        $returnUrl = $this->generateUrl('mautic_webhook_index', array('page' => $page));

        $postActionVars = array(
            'returnUrl'       => $returnUrl,
            'viewParameters'  => array('page' => $page),
            'contentTemplate' => 'WebhookBundle:Webhook:index',
            'passthroughVars' => array(
                'activeLink'    => 'mautic_webhook_index',
                'mauticContent' => 'webhook'
            )
        );

        //not found
        if ($entity === null) {
            return $this->postActionRedirect(
                array_merge($postActionVars, array(
                    'flashes' => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.webhook.webhook.error.notfound',
                            'msgVars' => array('%id%' => $objectId)
                        )
                    )
                ))
            );
        }

        // get the list of types from the model
        $availableEvents  = $model->getAvailableEvents();

        // get the network type from the request on submit. helpful for validation error
        // rebuilds structure of the form when it gets updated on submit
        $selectedEvents = ($this->request->getMethod() == 'POST') ? $this->request->request->get('webhook[events]', '', true) : $entity->getEvents();

        // build the form
        $form  = $model->createForm($entity, $this->get('form.factory'), $action, array(
            // pass through the types and the selected default type
            'availableEvents'   => $availableEvents,
            // 'selectedEvents'    => $selectedEvents
        ));

        $cancelled = $this->isFormCancelled($form);

        ///Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    //form is valid so process the data
                    $model->saveEntity($entity, $form->get('buttons')->get('save')->isClicked());

                    // update the audit log
                    $this->updateAuditLog($entity, 'update');

                    $this->addFlash(
                        'mautic.core.notice.updated',
                        array(
                            '%name%'      => $entity->getTitle(),
                            '%menu_link%' => 'mautic_email_index',
                            '%url%'       => $this->generateUrl(
                                'mautic_webhook_action',
                                array(
                                    'objectAction' => 'edit',
                                    'objectId'     => $entity->getId()
                                )
                            )
                        ),
                        'warning'
                    );
                }
            } else {
                $model->unlockEntity($entity);
            }

            if ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked())) {
                $viewParameters = array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId()
                );

                return $this->postActionRedirect(
                    array_merge($postActionVars, array(
                        'returnUrl'       => $this->generateUrl('mautic_webhook_action', $viewParameters),
                        'viewParameters'  => $viewParameters,
                        'contentTemplate' => 'WebhookBundle:Webhook:view'
                    ))
                );
            }
        } else {
            //lock the entity
            $model->lockEntity($entity);
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'tmpl'           => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'entity'         => $entity,
                'form'           => $form->createView(),
            ),
            'contentTemplate' => 'WebhookBundle:Webhook:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_webhook_index',
                'mauticContent' => 'webhook',
                'route'         => $this->generateUrl('mautic_webhook_action', array(
                    'objectAction' => 'edit',
                    'objectId'     => $entity->getId()
                ))
            )
        ));
    }

    /**
     * Shows a webhook record
     *
     * @param int $objectId Webhook ID
     * @param int $webhookPage
     *
     * @return HttpFoundation\JsonResponse|HttpFoundation\Response
     */
    public function viewAction ($objectId)
    {
        /* @type \Mautic\WebhookBundle\Model\WebhookModel $model */
        $model    = $this->factory->getModel('webhook');
        $entity   = $model->getEntity($objectId);
        $security = $this->factory->getSecurity();
        $page     = $this->factory->getSession()->get('mautic.webhook.page', 1);

        if ($entity === null) {
            return $this->postActionRedirect(array(
                'returnUrl'       => $this->generateUrl('mautic_webhook_index', array('page' => $page)),
                'viewParameters'  => array('page' => $page),
                'contentTemplate' => 'MauticWebhookBundle:Webhook:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_webhook_index',
                    'mauticContent' => 'webhook'
                ),
                'flashes'         => array(
                    array(
                        'type'    => 'error',
                        'msg'     => 'mautic.webhook.webhook.error.notfound',
                        'msgVars' => array('%id%' => $objectId)
                    )
                )
            ));
        } elseif (!$security->hasEntityAccess('webhook:webhooks:viewown', 'webhook:webhooks:viewother', $entity->getCreatedBy())) {
            return $this->accessDenied();
        }

        // Set filters
        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject('webhook', $entity->getId(), $entity->getDateAdded());

        return $this->delegateView(array(
            'viewParameters'   => array(
                'webhook'      => $entity,
                'page'         => $page,
                'tmpl'         => $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index',
                'security'     => $security,
                'logs'          => $logs,
                'permissions'  => $security->isGranted(array(
                    'webhook:webhooks:viewown',
                    'webhook:webhooks:viewother',
                    'webhook:webhooks:create',
                    'webhook:webhooks:editown',
                    'webhook:webhooks:editother',
                    'webhook:webhooks:deleteown',
                    'webhook:webhooks:deleteother'
                ), "RETURN_ARRAY"),
            ),
            'contentTemplate' => 'MauticWebhookBundle:Webhook:details.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_webhook_index',
                'mauticContent' => 'webhook',
                'route'         => $this->generateUrl('mautic_webhook_action', array(
                    'objectAction' => 'view',
                    'objectId'     => $entity->getId(),
                    'page'         => $page,
                )),
            )
        ));
    }

    /*
    * Update the audit log
    */
    public function updateAuditLog(\Mautic\WebhookBundle\Entity\Webhook $webhook, $action)
    {
        $log = array(
            "bundle"     => "WebhookBundle",
            "object"     => "webhook",
            "objectId"   => $webhook->getId(),
            "action"     => $action,
            "details"    => array('name' => $webhook->getTitle()),
            "ipAddress"  => $this->factory->getIpAddressFromRequest()
        );

        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}