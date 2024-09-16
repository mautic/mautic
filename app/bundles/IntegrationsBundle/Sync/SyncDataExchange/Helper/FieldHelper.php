<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\IntegrationsBundle\Event\MauticSyncFieldsLoadEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
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
    private $requiredFieldList = [];

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

    public function getFieldList(string $object): array
    {
        if (!isset($this->fieldList[$object])) {
            $this->fieldList[$object] = $this->fieldModel->getFieldListWithProperties($object);
        }

        return $this->fieldList[$object];
    }

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
            case 'select':
                return NormalizedValueDAO::SELECT_TYPE;
            case 'multiselect':
                return NormalizedValueDAO::MULTISELECT_TYPE;
            default:
                return NormalizedValueDAO::STRING_TYPE;
        }
    }

    /**
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
            uksort($this->syncFields[$objectName], 'strnatcmp');

            return $this->syncFields[$objectName];
        }

        // Mautic contacts have "pseudo" fields such as channel do not contact, timeline, etc.
        $channels = $this->channelListHelper->getFeatureChannels([LeadModel::CHANNEL_FEATURE], true);
        foreach ($channels as $label => $channel) {
            $this->syncFields[$objectName]['mautic_internal_dnc_'.$channel] = $this->translator->trans('mautic.integration.sync.channel_dnc', ['%channel%' => $label]);
        }

        // Add the timeline link
        $this->syncFields[$objectName]['mautic_internal_contact_timeline'] = $this->translator->trans('mautic.integration.sync.contact_timeline');

        uksort($this->syncFields[$objectName], 'strnatcmp');

        return $this->syncFields[$objectName];
    }

    public function getRequiredFields(string $object): array
    {
        if (isset($this->requiredFieldList[$object])) {
            return $this->requiredFieldList[$object];
        }

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
            $this->requiredFieldList[$object] = $requiredFields;

            return $this->requiredFieldList[$object];
        }

        $uniqueIdentifierFields = $this->fieldModel->getUniqueIdentifierFields(
            [
                'isPublished' => true,
                'object'      => $object,
            ]
        );

        $this->requiredFieldList[$object] = array_merge($requiredFields, $uniqueIdentifierFields);

        return $this->requiredFieldList[$object];
    }
}
