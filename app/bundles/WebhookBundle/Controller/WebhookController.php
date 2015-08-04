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
    public function __construct()
    {
        $this->setStandardParameters(
            'webhook.webhook', // model name
            'webhook:webhooks', // permission base
            'mautic_webhook', // route base
            'mautic_webhook', // session base
            'mautic.webhook', // lang string base
            'MauticWebhookBundle:Webhook', // template base
            'mautic_webhook', // activeLink
            'mauticWebhook' // mauticContent
        );
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction($page = 1)
    {
        return parent::indexStandard($page);
    }

    /**
     * Generates new form and processes post data
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function newAction()
    {
        return parent::newStandard();
    }

    /**
     * Generates edit form and processes post data
     *
     * @param int  $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|Response
     */
    public function editAction($objectId, $ignorePost = false)
    {
        //$delegateArgs = parent::editStandard($objectId, $ignorePost);

        $model  = $this->factory->getModel($this->modelName);
        $entity = $model->getEntity($objectId);
        $action = $this->generateUrl($this->routeBase.'_action', array('objectAction' => 'edit', 'objectId' => $objectId));
        $events = $model->getEvents();

        $form = $model->createForm($entity, $this->get('form.factory'), $action, array(
            // pass through the types and the selected default type
            'events'   => $events
        ));

        if (isset($deleteArgs['viewParameters'])) {
            if (isset($deleteArgs['viewParameters'])) {
                $delegateArgs['viewParameters']['form'] = $form->createView();
            }
        }

        return $delegateArgs;
    }

    /**
     * Displays details on a Focus
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return parent::viewStandard($objectId, 'webhook', 'mautic.webhook');
    }

    /**
     * Clone an entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        return parent::cloneStandard($objectId);
    }

    /**
     * Deletes the entity
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        return parent::deleteStandard($objectId);
    }

    /**
     * Deletes a group of entities
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return parent::batchDeleteStandard();
    }

    /**
     * @param $args
     * @param $view
     */
    /* public function customizeViewArguments($args, $view)
    {
        if ($view == 'new' || $view == 'edit') {
            // @todo Remove for download version

            /** @var \Mautic\AllydeBundle\Helper\HostedHelper $hostedHelper
            $hostedHelper = $this->factory->getHelper('allyde.hosted');
            $params       = $hostedHelper->getParams();
            $args['viewParameters']['instance'] = $params['hosted_instance'];
        } elseif ($view == 'view') {
            /** @var \MauticAddon\MpowerFocusBundle\Entity\Focus $item
            $item = $args['viewParameters']['item'];

            /** @var \MauticAddon\MpowerFocusBundle\Model\FocusModel $model
            $model = $this->factory->getModel('addon.mpowerFocus.focus');
            $stats = $model->getStats($item);

            $args['viewParameters']['stats'] = $stats;
        }

        return $args;
    }*/
}