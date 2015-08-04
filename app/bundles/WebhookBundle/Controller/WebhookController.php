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
            'webhook.webhook',
            'webhook:webhooks',
            'mautic_webhook',
            'mautic_webhook',
            'mautic.webhook',
            'MauticWebhookBundle:Webhook',
            'mautic_webhook',
            'mauticWebhook'
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
        return parent::editStandard($objectId, $ignorePost);
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