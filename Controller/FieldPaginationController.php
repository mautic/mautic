<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Form\Type\FilteredFieldsTrait;
use MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldMappingType;
use MauticPlugin\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FieldPaginationController extends CommonController
{
    use FilteredFieldsTrait;

    /**
     * @param string  $integration
     * @param string  $object
     * @param int     $page
     * @param Request $request
     *
     * @return mixed
     */
    public function paginateAction(string $integration, string $object, int $page, Request $request)
    {
        // Check ACL
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        // Find the integration
        /** @var ConfigIntegrationsHelper $integrationsHelper */
        $integrationsHelper = $this->get('mautic.integrations.helper.config_integrations');
        try {
            /** @var ConfigFormSyncInterface $integrationObject */
            $integrationObject        = $integrationsHelper->getIntegration($integration);
            $integrationConfiguration = $integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        $fieldModel = $this->get('mautic.lead.model.field');

        $keyword         = $request->get('keyword');
        $featureSettings = $integrationConfiguration->getFeatureSettings();
        $currentFields   = $this->getFields($featureSettings, $integration, $object);

        $this->filterFields($integrationObject, $object, $keyword, $page);

        // Create the form
        $form = $this->get('form.factory')->create(
            IntegrationSyncSettingsObjectFieldMappingType::class,
            $currentFields,
            [
                'integrationFields' => $this->getFilteredFields(),
                'mauticFields'      => $fieldModel->getFieldList(false),
                'page'              => $page,
                'keyword'           => $keyword,
                'totalFieldCount'   => $this->getTotalFieldCount(),
                'object'            => $object,
                'integration'       => $integration,
                'csrf_protection'   => false,
            ]
        );

        $html = $this->render(
            'IntegrationsBundle:Config:field_mapping.html.php',
            [
                'form'        => $form->createView(),
                'integration' => $integration,
                'object'      => $object,
                'page'        => $page,
            ]
        )->getContent();

        $prefix   = "integration_config[featureSettings][sync][fieldMappings][$object]";
        $idPrefix = str_replace(['][', '[', ']'], '_', $prefix);
        if (substr($idPrefix, -1) == '_') {
            $idPrefix = substr($idPrefix, 0, -1);
        }

        $formType = 'integration_sync_settings_object_field_mapping';
        $html     = preg_replace('/'.$formType.'\[(.*?)\]/', $prefix.'[$1]', $html);
        $html     = str_replace($formType, $idPrefix, $html);

        return new JsonResponse(
            [
                'success' => 1,
                'html'    => $html,
            ]
        );
    }

    /**
     * @param array  $featureSettings
     * @param string $integration
     * @param string $object
     *
     * @return array
     */
    private function getFields(array $featureSettings, string $integration, string $object): array
    {
        $fields = (isset($featureSettings['sync']['fieldMappings'][$object])) ? $featureSettings['sync']['fieldMappings'][$object] : [];

        // Pull those changed from session
        $session       = $this->get('session');
        $sessionFields = $session->get(sprintf("%s-fields", $integration), []);

        if (!isset($sessionFields[$object])) {
            return $fields;
        }

        return array_merge_recursive($fields, $sessionFields[$object]);
    }
}
