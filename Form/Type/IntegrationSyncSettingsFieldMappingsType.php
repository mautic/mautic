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


use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * IntegrationSyncSettingsFieldMappingsType constructor.
     *
     * @param TranslatorInterface $translator
     * @param FieldModel          $fieldModel
     */
    public function __construct(TranslatorInterface $translator, FieldModel $fieldModel)
    {
        $this->translator = $translator;
        $this->fieldModel = $fieldModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException("objects must be an array");
        }

        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException("integrationObject must be an instance of ConfigFormSyncInterface");
        }

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $requiredFields = $integrationObject->getRequiredFieldsForMapping($objectName);
            $optionalFields = $integrationObject->getOptionalFieldsForMapping($objectName);

            $builder->add(
                $objectName,
                IntegrationSyncSettingsObjectFieldMappingType::class,
                [
                    'label'                     => false,
                    'requiredIntegrationFields' => $requiredFields,
                    'optionalIntegrationFields' => $optionalFields,
                    'mauticFields'              => $this->fieldModel->getFieldList(false),
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
                'objects'
            ]
        );
    }
}