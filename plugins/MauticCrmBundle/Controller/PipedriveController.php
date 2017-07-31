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
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\CompanyImport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\LeadImport;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\OwnerImport;
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

    const DEAL_ADD_EVENT    = 'added.deal';
    const DEAL_UPDATE_EVENT = 'updated.deal';
    const DEAL_DELETE_EVENT = 'deleted.deal';

    const PIPELINE_ADD_EVENT    = 'added.pipeline';
    const PIPELINE_UPDATE_EVENT = 'updated.pipeline';
    const PIPELINE_DELETE_EVENT = 'deleted.pipeline';

    const PRODUCT_ADD_EVENT    = 'added.product';
    const PRODUCT_UPDATE_EVENT = 'updated.product';
    const PRODUCT_DELETE_EVENT = 'deleted.product';

    const STAGE_ADD_EVENT    = 'added.stage';
    const STAGE_UPDATE_EVENT = 'updated.stage';
    const STAGE_DELETE_EVENT = 'deleted.stage';

    const USER_ADD_EVENT    = 'added.user';
    const USER_UPDATE_EVENT = 'updated.user';

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
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
                case self::DEAL_ADD_EVENT:
                    $dealImport = $this->getDealImport($pipedriveIntegration);
                    $dealImport->create($data);
                    break;
                case self::DEAL_UPDATE_EVENT:
                    $dealImport = $this->getDealImport($pipedriveIntegration);
                    $dealImport->update($data);
                    break;
                case self::DEAL_DELETE_EVENT:
                    $dealImport = $this->getDealImport($pipedriveIntegration);
                    $dealImport->delete($params['previous']);
                    break;
                case self::PIPELINE_ADD_EVENT:
                    $pipelineImport = $this->getPipelineImport($pipedriveIntegration);
                    $pipelineImport->create($data);
                    break;
                case self::PIPELINE_UPDATE_EVENT:
                    $pipelineImport = $this->getPipelineImport($pipedriveIntegration);
                    $pipelineImport->update($data);
                    break;
                case self::PIPELINE_DELETE_EVENT:
                    $pipelineImport = $this->getPipelineImport($pipedriveIntegration);
                    $pipelineImport->delete($params['previous']);
                    break;
                case self::PRODUCT_ADD_EVENT:
                    $productImport = $this->getProductImport($pipedriveIntegration);
                    $productImport->create($data);
                    break;
                case self::PRODUCT_UPDATE_EVENT:
                    $productImport = $this->getProductImport($pipedriveIntegration);
                    $productImport->update($data);
                    break;
                case self::PRODUCT_DELETE_EVENT:
                    $productImport = $this->getProductImport($pipedriveIntegration);
                    $productImport->delete($params['previous']);
                    break;
                case self::STAGE_ADD_EVENT:
                    $stageImport = $this->getStageImport($pipedriveIntegration);
                    $stageImport->create($data);
                    break;
                case self::STAGE_UPDATE_EVENT:
                    $stageImport = $this->getStageImport($pipedriveIntegration);
                    $stageImport->update($data);
                    break;
                case self::STAGE_DELETE_EVENT:
                    $stageImport = $this->getStageImport($pipedriveIntegration);
                    $stageImport->delete($params['previous']);
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

    /**
     * @param $integration
     *
     * @return LeadImport
     */
    private function getLeadImport($integration)
    {
        /** @var LeadImport $leadImport */
        $leadImport = $this->get('mautic_integration.pipedrive.import.lead');
        $leadImport->setIntegration($integration);

        return $leadImport;
    }

    /**
     * @param $integration
     *
     * @return CompanyImport
     */
    private function getCompanyImport($integration)
    {
        /** @var CompanyImport $companyImport */
        $companyImport = $this->get('mautic_integration.pipedrive.import.company');
        $companyImport->setIntegration($integration);

        return $companyImport;
    }

    private function getDealImport($integration)
    {
        $dealImport = $this->get('mautic_integration.pipedrive.import.deal');
        $dealImport->setIntegration($integration);

        return $dealImport;
    }

    /**
     * @param $integration
     *
     * @return OwnerImport
     */
    private function getOwnerImport($integration)
    {
        /** @var OwnerImport $ownerImport */
        $ownerImport = $this->get('mautic_integration.pipedrive.import.owner');
        $ownerImport->setIntegration($integration);

        return $ownerImport;
    }

    private function getPipelineImport($integration)
    {
        $pipelineImport = $this->get('mautic_integration.pipedrive.import.pipeline');
        $pipelineImport->setIntegration($integration);

        return $pipelineImport;
    }

    private function getProductImport($integration)
    {
        $productImport = $this->get('mautic_integration.pipedrive.import.product');
        $productImport->setIntegration($integration);

        return $productImport;
    }

    private function getStageImport($integration)
    {
        $stageImport = $this->get('mautic_integration.pipedrive.import.stage');
        $stageImport->setIntegration($integration);

        return $stageImport;
    }

    /**
     * @param Request              $request
     * @param PipedriveIntegration $pipedriveIntegration
     *
     * @return bool
     */
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
