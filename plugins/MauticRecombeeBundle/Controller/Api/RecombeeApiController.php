<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecombeeBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticRecombeeBundle\Helper\RecombeeHelper;
use Recombee\RecommApi\Exceptions as Ex;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class RecombeeApiController.
 */
class RecombeeApiController extends CommonApiController
{
    /**
     * @var RecombeeHelper
     */
    protected $recombeeHelper;

    private $components = ['CartAddition', 'Purchase', 'Rating', 'Bookmark', 'DetailView'];

    private $actions = ['Add', 'Delete'];

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->leadModel      = $this->getModel('lead.lead');
        $this->recombeeHelper = $this->container->get('mautic.recombee.helper');
        parent::initialize($event);
    }

    /**
     * @param $compnent
     * @param $user
     * @param $action
     * @param $item
     *
     * @return array|Response
     */
    public function processAction($component, $user, $action, $item)
    {
        $response = ['success' => false];

        if (!in_array($component, $this->components) || !in_array($action, $this->actions)) {
            return $this->returnError(
                $this->translator->trans('mautic.plugin.recombee.api.wrong.component.action', [], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $lead = $this->leadModel->getEntity($user);
        if (!$lead instanceof Lead || !$lead->getId()) {
            return $this->returnError(
                $this->translator->trans('mautic.plugin.recombee.contact.doesnt.exist', [], 'validators'),
                Response::HTTP_BAD_REQUEST
            );
        }

        // change method from POST to DELETE
        if ($action == 'delete') {
            $this->request->setMethod('DELETE');
        }

        try {
            $class = 'Recombee\\RecommApi\\Requests\\'.$action.$component;
            $this->recombeeHelper->getClient()->send(new $class($user, $item, ['cascadeCreate' => true]));
            $response = ['success' => true];
        } catch (Ex\ApiException $e) {
            return $this->returnError(
                $this->translator->trans($e->getMessage(), [], 'validators'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $view = $this->view($response);

        return $this->handleView($view);
    }
}
