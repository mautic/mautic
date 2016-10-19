<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\ApiEvents;
use Mautic\ApiBundle\Event as Events;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ApiSubscriber.
 */
class ApiSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * ApiSubscriber constructor.
     *
     * @param IpLookupHelper       $ipLookupHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param AuditLogModel        $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, CoreParametersHelper $coreParametersHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->auditLogModel        = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST         => ['onKernelRequest', 255],
            ApiEvents::CLIENT_POST_SAVE   => ['onClientPostSave', 0],
            ApiEvents::CLIENT_POST_DELETE => ['onClientDelete', 0],
        ];
    }

    /**
     * Check for API requests and throw denied access if API is disabled.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $apiEnabled = $this->coreParametersHelper->getParameter('api_enabled');
        $request    = $event->getRequest();
        $requestUrl = $request->getRequestUri();

        // Check if /oauth or /api
        $isApiRequest = (strpos($requestUrl, '/oauth') !== false || strpos($requestUrl, '/api') !== false);
        defined('MAUTIC_API_REQUEST') or define('MAUTIC_API_REQUEST', $isApiRequest);

        if ($isApiRequest && !$apiEnabled) {
            throw new AccessDeniedHttpException(
                $this->translator->trans(
                    'mautic.core.url.error.401',
                    [
                        '%url%' => $request->getRequestUri(),
                    ]
                )
            );
        }
    }

    /**
     * Add a client change entry to the audit log.
     *
     * @param Events\ClientEvent $event
     */
    public function onClientPostSave(Events\ClientEvent $event)
    {
        $client = $event->getClient();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'api',
                'object'    => 'client',
                'objectId'  => $client->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a role delete entry to the audit log.
     *
     * @param Events\ClientEvent $event
     */
    public function onClientDelete(Events\ClientEvent $event)
    {
        $client = $event->getClient();
        $log    = [
            'bundle'    => 'api',
            'object'    => 'client',
            'objectId'  => $client->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $client->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
