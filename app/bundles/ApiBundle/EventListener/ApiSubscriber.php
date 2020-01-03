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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\TranslatorInterface;

class ApiSubscriber implements EventSubscriberInterface
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        IpLookupHelper $ipLookupHelper,
        CoreParametersHelper $coreParametersHelper,
        AuditLogModel $auditLogModel,
        TranslatorInterface $translator
    ) {
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->auditLogModel        = $auditLogModel;
        $this->translator           = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST         => ['onKernelRequest', 255],
            KernelEvents::RESPONSE        => ['onKernelResponse', 0],
            ApiEvents::CLIENT_POST_SAVE   => ['onClientPostSave', 0],
            ApiEvents::CLIENT_POST_DELETE => ['onClientDelete', 0],
        ];
    }

    /**
     * Check for API requests and throw denied access if API is disabled.
     *
     * @throws AccessDeniedHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $apiEnabled   = $this->coreParametersHelper->getParameter('api_enabled');
        $isApiRequest = $this->isApiRequest($event);

        if ($isApiRequest && !$apiEnabled) {
            throw new AccessDeniedHttpException($this->translator->trans('mautic.api.error.api.disabled'));
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response   = $event->getResponse();
        $content    = $response->getContent();
        $statusCode = $response->getStatusCode();

        if ($this->isApiRequest($event) && false !== strpos($content, 'error')) {
            // Override api messages with something useful
            if ($data = json_decode($content, true)) {
                $type = null;
                if (isset($data['error'])) {
                    $error   = $data['error'];
                    $message = false;
                    if (is_array($error)) {
                        if (!isset($error['message'])) {
                            return;
                        }

                        // Catch useless oauth1a errors
                        $error = $error['message'];
                    }

                    switch ($error) {
                        case 'access_denied':
                            if ($this->isBasicAuth($event->getRequest())) {
                                if ($this->coreParametersHelper->getParameter('api_enable_basic_auth')) {
                                    $message = $this->translator->trans('mautic.api.error.basic.auth.invalid.credentials');
                                } else {
                                    $message = $this->translator->trans('mautic.api.error.basic.auth.disabled');
                                }
                            } else {
                                $message = $this->translator->trans('mautic.api.auth.error.accessdenied');
                            }

                            $type = $error;
                            break;
                        default:
                            if (isset($data['error_description'])) {
                                $message = $data['error_description'];
                                $type    = $error;
                            } elseif ($this->translator->hasId('mautic.api.auth.error.'.$error)) {
                                $message = $this->translator->trans('mautic.api.auth.error.'.$error);
                                $type    = $error;
                            }
                    }

                    if ($message) {
                        $response = new JsonResponse(
                            [
                                'errors' => [
                                    [
                                        'message' => $message,
                                        'code'    => $response->getStatusCode(),
                                        'type'    => $type,
                                    ],
                                ],
                            ],
                            $statusCode
                        );

                        $event->setResponse($response);
                    }
                }
            }
        }
    }

    public function isBasicAuth(Request $request)
    {
        try {
            return 0 === strpos(strtolower($request->headers->get('Authorization')), 'basic');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Add a client change entry to the audit log.
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

    /**
     * @param $event
     *
     * @return bool
     */
    private function isApiRequest($event)
    {
        $request    = $event->getRequest();
        $requestUrl = $request->getRequestUri();

        // Check if /oauth or /api
        $isApiRequest = (false !== strpos($requestUrl, '/oauth') || false !== strpos($requestUrl, '/api'));

        defined('MAUTIC_API_REQUEST') or define('MAUTIC_API_REQUEST', $isApiRequest);

        return $isApiRequest;
    }
}
