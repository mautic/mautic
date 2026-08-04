<?php

namespace Mautic\NotificationBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Model\NotificationModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Notification>
 */
final class NotificationApiController extends CommonApiController
{
    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private readonly ContactTracker $contactTracker,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        UserHelper $userHelper,
        NotificationModel $notificationModel,
        private readonly LeadModel $leadModel,
    ) {
        $this->model           = $notificationModel;
        $this->entityClass     = Notification::class;
        $this->entityNameOne   = 'notification';
        $this->entityNameMulti = 'notifications';

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $userHelper);
    }

    /**
     * Receive Web Push subscription request.
     */
    public function subscribeAction(Request $request): JsonResponse
    {
        $osid = $request->get('osid');
        if ($osid) {
            if ($currentLead = $this->contactTracker->getContact()) {
                $currentLead->addPushIDEntry($osid);
                $this->leadModel->saveEntity($currentLead);
            }

            return new JsonResponse(['success' => true, 'osid' => $osid], 200, ['Access-Control-Allow-Origin' => '*']);
        }

        return new JsonResponse(['success' => 'false'], 200, ['Access-Control-Allow-Origin' => '*']);
    }
}
