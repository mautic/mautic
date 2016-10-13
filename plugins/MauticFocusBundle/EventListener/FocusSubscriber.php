<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\MauticFocusBundle\Event\FocusEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class FocusSubscriber.
 */
class FocusSubscriber extends CommonSubscriber
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var IpLookupHelper
     */
    protected $ipHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * FocusSubscriber constructor.
     *
     * @param RouterInterface $router
     * @param IpLookupHelper  $ipLookupHelper
     * @param AuditLogModel   $auditLogModel
     */
    public function __construct(RouterInterface $router, IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->router        = $router;
        $this->ipHelper      = $ipLookupHelper;
        $this->auditLogModel = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST    => ['onKernelRequest', 0],
            FocusEvents::POST_SAVE   => ['onFocusPostSave', 0],
            FocusEvents::POST_DELETE => ['onFocusDelete', 0],
        ];
    }

    /*
     * Check and hijack the form's generate link if the ID has mf- in it
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            // get the current event request
            $request    = $event->getRequest();
            $requestUri = $request->getRequestUri();

            $formGenerateUrl = $this->router->generate('mautic_form_generateform');

            if (strpos($requestUri, $formGenerateUrl) !== false) {
                $id = InputHelper::_($this->request->get('id'));
                if (strpos($id, 'mf-') === 0) {
                    $mfId             = str_replace('mf-', '', $id);
                    $focusGenerateUrl = $this->router->generate('mautic_focus_generate', ['id' => $mfId]);

                    $event->setResponse(new RedirectResponse($focusGenerateUrl));
                }
            }
        }
    }

    /**
     * Add an entry to the audit log.
     *
     * @param FocusEvent $event
     */
    public function onFocusPostSave(FocusEvent $event)
    {
        $entity = $event->getFocus();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'focus',
                'object'    => 'focus',
                'objectId'  => $entity->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param FocusEvent $event
     */
    public function onFocusDelete(FocusEvent $event)
    {
        $entity = $event->getFocus();
        $log    = [
            'bundle'    => 'focus',
            'object'    => 'focus',
            'objectId'  => $entity->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $entity->getName()],
            'ipAddress' => $this->ipHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
