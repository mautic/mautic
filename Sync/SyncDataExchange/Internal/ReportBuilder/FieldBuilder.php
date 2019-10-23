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

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class FieldBuilder
{
    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var ContactObjectHelper
     */
    private $contactObjectHelper;

    /**
     * @var array
     */
    private $mauticObject;

    /**
     * @var RequestObjectDAO
     */
    private $requestObject;

    /**
     * @param Router              $router
     * @param FieldHelper         $fieldHelper
     * @param ContactObjectHelper $contactObjectHelper
     */
    public function __construct(Router $router, FieldHelper $fieldHelper, ContactObjectHelper $contactObjectHelper)
    {
        $this->valueNormalizer = new ValueNormalizer();

        $this->router              = $router;
        $this->fieldHelper         = $fieldHelper;
        $this->contactObjectHelper = $contactObjectHelper;
    }

    /**
     * @param string           $field
     * @param array            $mauticObject
     * @param RequestObjectDAO $requestObject
     * @param string           $integration
     *
     * @return ReportFieldDAO
     *
     * @throws FieldNotFoundException
     */
    public function buildObjectField(
        string $field,
        array $mauticObject,
        RequestObjectDAO $requestObject,
        string $integration
    ) {
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
        if (0 === strpos($field, 'mautic_internal_dnc_')) {
            return $this->addDoNotContactField($field);
        }

        // Special handling of timeline URL
        if ('mautic_internal_contact_timeline' === $field) {
            return $this->addContactTimelineField($integration, $field);
        }

        return $this->addCustomField($field);
    }

    /**
     * @param string $field
     *
     * @return ReportFieldDAO
     */
    private function addContactIdField(string $field)
    {
        $normalizedValue = new NormalizedValueDAO(
            NormalizedValueDAO::INT_TYPE,
            $this->mauticObject['id']
        );

        return new ReportFieldDAO($field, $normalizedValue);
    }

    /**
     * @param string $field
     * @param int    $ownerId
     *
     * @return ReportFieldDAO
     */
    private function createOwnerIdReportFieldDAO(string $field, int $ownerId)
    {
        return new ReportFieldDAO(
            $field,
            new NormalizedValueDAO(
                NormalizedValueDAO::INT_TYPE,
                $ownerId
            )
        );
    }

    /**
     * @param string $field
     *
     * @return ReportFieldDAO
     */
    private function addDoNotContactField(string $field)
    {
        $channel = str_replace('mautic_internal_dnc_', '', $field);

        $normalizedValue = new NormalizedValueDAO(
            NormalizedValueDAO::INT_TYPE,
            $this->contactObjectHelper->getDoNotContactStatus((int) $this->mauticObject['id'], $channel)
        );

        return new ReportFieldDAO($field, $normalizedValue);
    }

    /**
     * @param string $integration
     * @param string $field
     *
     * @return ReportFieldDAO
     */
    private function addContactTimelineField(string $integration, string $field)
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
     * @param string $field
     *
     * @return ReportFieldDAO
     *
     * @throws FieldNotFoundException
     */
    private function addCustomField(string $field)
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
            in_array($field, $requiredFields) ? ReportFieldDAO::FIELD_REQUIRED : ReportFieldDAO::FIELD_UNCHANGED
        );
    }
}
