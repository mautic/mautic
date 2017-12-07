<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class PipedriveController.
 */
class PipedriveController extends CommonController
{
    const INTEGRATION_NAME = 'Pipedrive';

    const LEAD_ADDED_EVENT  = 'added.person';
    const LEAD_UPDATE_EVENT = 'updated.person';
    const LEAD_DELETE_EVENT = 'deleted.person';

    const COMPANY_ADD_EVENT    = 'added.organization';
    const COMPANY_UPDATE_EVENT = 'updated.organization';
    const COMPANY_DELETE_EVENT = 'deleted.organization';

    const USER_ADD_EVENT    = 'added.user';
    const USER_UPDATE_EVENT = 'updated.user';

    public function webhookAction(Request $request)
    {
        $integrationHelper    = $this->get('mautic.helper.integration');
        $pipedriveIntegration = $integrationHelper->getIntegrationObject(self::INTEGRATION_NAME);

        if (!$pipedriveIntegration || !$pipedriveIntegration->getIntegrationSettings()->getIsPublished()) {
            return new JsonResponse([
                'status' => 'Integration turned off',
            ], Response::HTTP_OK);
        }

        if (!$this->validCredential($request, $pipedriveIntegration)) {
            throw new UnauthorizedHttpException('Basic');
        }

        $params   = json_decode($request->getContent(), true);
        $data     = $params['current'];
        $response = [
            'status' => 'ok',
        ];

        try {
            switch ($params['event']) {
                case self::LEAD_ADDED_EVENT:
                    $leadImport = $this->getLeadImport($pipedriveIntegration);
                    $leadImport->create($data);
                    break;
                case self::LEAD_UPDATE_EVENT:
                    $leadImport = $this->getLeadImport($pipedriveIntegration);
                    $leadImport->update($data);
                    break;
                case self::LEAD_DELETE_EVENT:
                    $leadImport = $this->getLeadImport($pipedriveIntegration);
                    $leadImport->delete($params['previous']);
                    break;
                case self::COMPANY_ADD_EVENT:
                    $companyImport = $this->getCompanyImport($pipedriveIntegration);
                    $companyImport->create($data);
                    break;
                case self::COMPANY_UPDATE_EVENT:
                    $companyImport = $this->getCompanyImport($pipedriveIntegration);
                    $companyImport->update($data);
                    break;
                case self::COMPANY_DELETE_EVENT:
                    $companyImport = $this->getCompanyImport($pipedriveIntegration);
                    $companyImport->delete($params['previous']);
                    break;
                case self::USER_ADD_EVENT:
                case self::USER_UPDATE_EVENT:
                $ownerImport = $this->getOwnerImport($pipedriveIntegration);
                $ownerImport->create($data[0]);
                    break;
                default:
                    $response = [
                        'status' => 'unsupported event',
                    ];

            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode());
        }

        return new JsonResponse($response, Response::HTTP_OK);
    }

    private function getLeadImport($integration)
    {
        $leadImport = $this->get('mautic_integration.pipedrive.import.lead');
        $leadImport->setIntegration($integration);

        return $leadImport;
    }

    private function getCompanyImport($integration)
    {
        $companyImport = $this->get('mautic_integration.pipedrive.import.company');
        $companyImport->setIntegration($integration);

        return $companyImport;
    }

    private function getOwnerImport($integration)
    {
        $ownerImport = $this->get('mautic_integration.pipedrive.import.owner');
        $ownerImport->setIntegration($integration);

        return $ownerImport;
    }

    private function validCredential(Request $request, PipedriveIntegration $pipedriveIntegration)
    {
        $headers = $request->headers->all();
        $keys    = $pipedriveIntegration->getKeys();

        if (!isset($headers['authorization']) || !isset($keys['user']) || !isset($keys['password'])) {
            return false;
        }

        $basicAuthBase64       = explode(' ', $headers['authorization'][0]);
        $decodedBasicAuth      = base64_decode($basicAuthBase64[1]);
        list($user, $password) = explode(':', $decodedBasicAuth);

        if ($keys['user'] == $user && $keys['password'] == $password) {
            return true;
        }

        return false;
    }
}
