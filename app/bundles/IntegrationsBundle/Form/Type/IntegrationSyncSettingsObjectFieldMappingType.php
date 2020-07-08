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

namespace Mautic\IntegrationsBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\InvalidFormOptionException;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsObjectFieldMappingType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    public function __construct(TranslatorInterface $translator, FieldHelper $fieldHelper)
    {
        $this->translator  = $translator;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @throws InvalidFormOptionException
     * @throws ObjectNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrationFields = $options['integrationFields'];

        /** @var ConfigFormSyncInterface $integrationObject */
        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException('integrationObject must be an instance of ConfigFormSyncInterface');
        }

        $objectName = $options['object'];
        foreach ($integrationFields as $fieldName => $fieldInfo) {
            if (!$fieldInfo instanceof MappedFieldInfoInterface) {
                throw new InvalidFormOptionException('integrationFields must contain an instance of MappedFieldInfoInterface');
            }

            $attr = [
                'label'        => $fieldInfo->getLabel(),
                'mauticFields' => $this->getMauticFields($integrationObject, $objectName),
                'required'     => $fieldInfo->showAsRequired(),
                'placeholder'  => $this->translator->trans('mautic.integration.sync_mautic_field'),
                'object'       => $objectName,
                'integration'  => $integrationObject->getName(),
                'field'        => $fieldInfo,
            ];

            if ($fieldInfo->hasTooltip()) {
                $attr['attr'] = [
                    'tooltip' => $fieldInfo->getTooltip(),
                    'class'   => 'form-control',
                ];
            }

            $builder->add(
                $fieldName,
                IntegrationSyncSettingsObjectFieldType::class,
                $attr
            );
        }

        $builder->add(
            'filter-keyword',
            TextType::class,
            [
                'label'  => false,
                'mapped' => false,
                'data'   => $options['keyword'],
                'attr'   => [
                    'class'            => 'form-control integration-keyword-filter',
                    'placeholder'      => $this->translator->trans('mautic.integration.sync_filter_fields'),
                    'data-object'      => $objectName,
                    'data-integration' => $integrationObject->getName(),
                ],
            ]
        );

        $builder->add(
            'filter-totalFieldCount',
            HiddenType::class,
            [
                'label'  => false,
                'mapped' => false,
                'data'   => $options['totalFieldCount'],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integrationFields',
                'page',
                'keyword',
                'totalFieldCount',
                'object',
                'integrationObject',
            ]
        );
    }

    /**
     * @throws ObjectNotFoundException
     */
    private function getMauticFields(ConfigFormSyncInterface $integrationObject, string $objectName): array
    {
        $mappedObjects = $integrationObject->getSyncMappedObjects();
        if (!isset($mappedObjects[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        $mauticObject = $mappedObjects[$objectName];

        return $this->fieldHelper->getSyncFields($mauticObject);
    }
}
