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

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\Event\MauticSyncFieldsLoadEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldHelper
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpresserHelper;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $fieldList = [];

    /**
     * @var array
     */
    private $syncFields = [];

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    /**
     * @param FieldModel                       $fieldModel
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     * @param ChannelListHelper                $channelListHelper
     * @param TranslatorInterface              $translator
     * @param EventDispatcherInterface         $eventDispatcher
     * @param ObjectProvider                   $objectProvider
     */
    public function __construct(
        FieldModel $fieldModel,
        VariableExpresserHelperInterface $variableExpresserHelper,
        ChannelListHelper $channelListHelper,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        ObjectProvider $objectProvider
    ) {
        $this->fieldModel              = $fieldModel;
        $this->variableExpresserHelper = $variableExpresserHelper;
        $this->channelListHelper       = $channelListHelper;
        $this->translator              = $translator;
        $this->eventDispatcher         = $eventDispatcher;
        $this->objectProvider          = $objectProvider;
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getFieldList(string $object): array
    {
        if (!isset($this->fieldList[$object])) {
            $this->fieldList[$object] = $this->fieldModel->getFieldListWithProperties($object);
        }

        return $this->fieldList[$object];
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getNormalizedFieldType(string $type): string
    {
        switch ($type) {
            case 'boolean':
                return NormalizedValueDAO::BOOLEAN_TYPE;
            case 'date':
            case 'datetime':
            case 'time':
                return NormalizedValueDAO::DATETIME_TYPE;
            case 'number':
                return NormalizedValueDAO::FLOAT_TYPE;
            default:
                return NormalizedValueDAO::STRING_TYPE;
        }
    }

    /**
     * @param string $objectName
     *
     * @return string
     *
     * @throws ObjectNotSupportedException
     */
    public function getFieldObjectName(string $objectName): string
    {
        try {
            return $this->objectProvider->getObjectByName($objectName)->getEntityName();
        } catch (ObjectNotFoundException $e) {
            // Throwing different exception to keep BC.
            throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }
    }

    /**
     * @param array $fieldChange
     *
     * @return FieldDAO
     */
    public function getFieldChangeObject(array $fieldChange): FieldDAO
    {
        $changeTimestamp = new \DateTimeImmutable($fieldChange['modified_at'], new \DateTimeZone('UTC'));
        $columnType      = $fieldChange['column_type'];
        $columnValue     = $fieldChange['column_value'];
        $newValue        = $this->variableExpresserHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));

        $reportFieldDAO = new FieldDAO($fieldChange['column_name'], $newValue);
        $reportFieldDAO->setChangeDateTime($changeTimestamp);

        return $reportFieldDAO;
    }

    /**
     * @param string $objectName
     *
     * @return array
     */
    public function getSyncFields(string $objectName): array
    {
        if (isset($this->syncFields[$objectName])) {
            return $this->syncFields[$objectName];
        }

        $this->syncFields[$objectName] = $this->fieldModel->getFieldList(
            false,
            true,
            [
                'isPublished' => true,
                'object'      => $objectName,
            ]
        );

        // Dispatch event to add possibility to add field from some listener
        $event                                     = new MauticSyncFieldsLoadEvent($objectName, $this->syncFields[$objectName]);
        $event                                     = $this->eventDispatcher->dispatch(IntegrationEvents::INTEGRATION_MAUTIC_SYNC_FIELDS_LOAD, $event);
        $this->syncFields[$event->getObjectName()] = $event->getFields();

        // Add ID as a read only field
        $this->syncFields[$objectName]['mautic_internal_id'] = $this->translator->trans('mautic.core.id');

        if (Contact::NAME !== $objectName) {
            uasort($this->syncFields[$objectName], 'strnatcmp');

            return $this->syncFields[$objectName];
        }

        // Mautic contacts have "pseudo" fields such as channel do not contact, timeline, etc.
        $channels = $this->channelListHelper->getFeatureChannels([LeadModel::CHANNEL_FEATURE], true);
        foreach ($channels as $label => $channel) {
            $this->syncFields[$objectName]['mautic_internal_dnc_'.$channel] = $this->translator->trans('mautic.integration.sync.channel_dnc', ['%channel%' => $label]);
        }

        // Add the timeline link
        $this->syncFields[$objectName]['mautic_internal_contact_timeline'] = $this->translator->trans('mautic.integration.sync.contact_timeline');

        uasort($this->syncFields[$objectName], 'strnatcmp');

        return $this->syncFields[$objectName];
    }

    /**
     * @param string $object
     *
     * @return array
     */
    public function getRequiredFields(string $object): array
    {
        $requiredFields = $this->fieldModel->getFieldList(
            false,
            false,
            [
                'isPublished' => true,
                'isRequired'  => true,
                'object'      => $object,
            ]
        );

        // We don't use unique identifier field for companies.
        if ('company' === $object) {
            return $requiredFields;
        }

        $uniqueIdentifierFields = $this->fieldModel->getUniqueIdentifierFields(
            [
                'isPublished' => true,
                'object'      => $object,
            ]
        );

        return array_merge($requiredFields, $uniqueIdentifierFields);
    }
}
