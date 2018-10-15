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
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    use FilteredFieldsTrait;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * IntegrationSyncSettingsFieldMappingsType constructor.
     *
     * @param FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
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
            throw new InvalidFormOptionException('objects must be an array');
        }

        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException('integrationObject must be an instance of ConfigFormSyncInterface');
        }

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($integrationObject, $objectName) {
                $error = null;
                try {
                    $this->filterFields($integrationObject, $objectName, null, 1);
                } catch (\Exception $exception) {
                    $error = $exception->getMessage();
                }

                $form = $event->getForm();
                $form->add(
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


                if ($error) {
                    $form[$objectName]->addError(new FormError($error));
                }
            });
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

        return $this->fieldHelper->getSyncFields($mauticObject);
    }
}
