<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class FieldBuilder
{
    private \Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer $valueNormalizer;

    private ?array $mauticObject = null;

    private ?\Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO $requestObject = null;

    public function __construct(
        private Router $router,
        private FieldHelper $fieldHelper,
        private ContactObjectHelper $contactObjectHelper
    ) {
        $this->valueNormalizer = new ValueNormalizer();
    }

    /**
     * @throws FieldNotFoundException
     */
    public function buildObjectField(
        string $field,
        array $mauticObject,
        RequestObjectDAO $requestObject,
        string $integration,
        string $defaultState = ReportFieldDAO::FIELD_CHANGED
    ): ReportFieldDAO {
        $this->mauticObject  = $mauticObject;
        $this->requestObject = $requestObject;

        // Special handling of the ID field
        if ('mautic_internal_id' === $field) {
            return $this->addContactIdField($field);
        }

        // Special handling of the owner ID field
        if ('owner_id' === $field) {
            return $this->createOwnerIdReportFieldDAO($field, (int) $mauticObject['owner_id']);
        }

        // Special handling of DNC fields
        if (str_starts_with($field, 'mautic_internal_dnc_')) {
            return $this->addDoNotContactField($field);
        }

        // Special handling of timeline URL
        if ('mautic_internal_contact_timeline' === $field) {
            return $this->addContactTimelineField($integration, $field);
        }

        return $this->addCustomField($field, $defaultState);
    }

    private function addContactIdField(string $field): ReportFieldDAO
    {
        $normalizedValue = new NormalizedValueDAO(
            NormalizedValueDAO::INT_TYPE,
            $this->mauticObject['id']
        );

        return new ReportFieldDAO($field, $normalizedValue);
    }

    private function createOwnerIdReportFieldDAO(string $field, int $ownerId): ReportFieldDAO
    {
        return new ReportFieldDAO(
            $field,
            new NormalizedValueDAO(
                NormalizedValueDAO::INT_TYPE,
                $ownerId
            )
        );
    }

    private function addDoNotContactField(string $field): ReportFieldDAO
    {
        $channel = str_replace('mautic_internal_dnc_', '', $field);

        $normalizedValue = new NormalizedValueDAO(
            NormalizedValueDAO::INT_TYPE,
            $this->contactObjectHelper->getDoNotContactStatus((int) $this->mauticObject['id'], $channel)
        );

        return new ReportFieldDAO($field, $normalizedValue);
    }

    private function addContactTimelineField(string $integration, string $field): ReportFieldDAO
    {
        $normalizedValue = new NormalizedValueDAO(
            NormalizedValueDAO::URL_TYPE,
            $this->router->generate(
                'mautic_plugin_timeline_view',
                [
                    'integration' => $integration,
                    'leadId'      => $this->mauticObject['id'],
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );

        return new ReportFieldDAO($field, $normalizedValue);
    }

    /**
     * @throws FieldNotFoundException
     */
    private function addCustomField(string $field, string $defaultState): ReportFieldDAO
    {
        // The rest should be Mautic custom fields and if not, just ignore
        $mauticFields = $this->fieldHelper->getFieldList($this->requestObject->getObject());
        if (!isset($mauticFields[$field])) {
            // Field must have been deleted or something so let's skip
            throw new FieldNotFoundException($field, $this->requestObject->getObject());
        }

        $requiredFields  = $this->requestObject->getRequiredFields();
        $fieldType       = $this->fieldHelper->getNormalizedFieldType($mauticFields[$field]['type']);
        $normalizedValue = $this->valueNormalizer->normalizeForMautic($fieldType, $this->mauticObject[$field]);

        return new ReportFieldDAO(
            $field,
            $normalizedValue,
            in_array($field, $requiredFields) ? ReportFieldDAO::FIELD_REQUIRED : $defaultState
        );
    }
}
