<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;

use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    use FilteredFieldsTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException('objects must be an array');
        }

        /** @var ConfigFormSyncInterface $integrationObject */
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
                        'label'              => false,
                        'integrationFields'  => $this->getFilteredFields(),
                        'page'               => 1,
                        'keyword'            => null,
                        'totalFieldCount'    => $this->getTotalFieldCount(),
                        'object'             => $objectName,
                        'integrationObject'  => $integrationObject,
                        'error_bubbling'     => false,
                        'allow_extra_fields' => true,
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integrationObject',
                'objects',
            ]
        );
    }
}
