<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    use FilteredFieldsTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * IntegrationSyncSettingsFieldMappingsType constructor.
     *
     * @param TranslatorInterface $translator
     * @param FieldModel          $fieldModel
     * @param ChannelListHelper   $channelListHelper
     */
    public function __construct(TranslatorInterface $translator, FieldModel $fieldModel, ChannelListHelper $channelListHelper)
    {
        $this->translator        = $translator;
        $this->fieldModel        = $fieldModel;
        $this->channelListHelper = $channelListHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     * @throws ObjectNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException('objects must be an array');
        }

        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException('integrationObject must be an instance of ConfigFormSyncInterface');
        }

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $this->filterFields($integrationObject, $objectName, null, 1);

            $builder->add(
                $objectName,
                IntegrationSyncSettingsObjectFieldMappingType::class,
                [
                    'label'                     => false,
                    'requiredIntegrationFields' => $this->getRequiredFields(),
                    'integrationFields'         => $this->getFilteredFields(),
                    'mauticFields'              => $this->getMauticFields($integrationObject, $objectName),
                    'page'                      => 1,
                    'keyword'                   => null,
                    'totalFieldCount'           => $this->getTotalFieldCount(),
                    'object'                    => $objectName,
                    'integration'               => $integrationObject->getName(),
                    'error_bubbling'            => false,
                    'allow_extra_fields'        => true,
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'integrationObject',
                'objects',
            ]
        );
    }

    /**
     * @param ConfigFormSyncInterface $integrationObject
     * @param string                  $objectName
     *
     * @return array
     * @throws ObjectNotFoundException
     */
    private function getMauticFields(ConfigFormSyncInterface $integrationObject, string $objectName)
    {
        $mappedObjects = $integrationObject->getSyncMappedObjects();
        if (!isset($mappedObjects[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        $mauticObject = $mappedObjects[$objectName];

        $coreFields = $this->fieldModel->getFieldList(
            false,
            true,
            [
                'isPublished' => true,
                'object'      => $mauticObject
            ]
        );

        // Add ID as a read only field
        $coreFields['mautic_internal_id'] = $this->translator->trans('mautic.core.id');

        if (MauticSyncDataExchange::OBJECT_CONTACT !== $mauticObject) {
            uasort($coreFields, 'strnatcmp');

            return $coreFields;
        }

        // Mautic contacts have "pseudo" fields such as channel do not contact, timeline, etc.
        $channels = $this->channelListHelper->getFeatureChannels([LeadModel::CHANNEL_FEATURE], true);
        foreach ($channels as $label => $channel) {
            $coreFields['mautic_internal_dnc_'.$channel] = $this->translator->trans('mautic.integration.sync.channel_dnc', ['%channel%' => $label]);
        }

        // Add the timeline link
        $coreFields['mautic_internal_contact_timeline'] = $this->translator->trans('mautic.integration.sync.contact_timeline');

        uasort($coreFields, 'strnatcmp');

        return $coreFields;
    }
}
