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
     * IntegrationSyncSettingsObjectFieldMappingType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['integrationFields'] as $field => $label) {
            $builder->add(
                $field,
                IntegrationSyncSettingsObjectFieldType::class,
                [
                    'label'        => $label,
                    'mauticFields' => $options['mauticFields'],
                    'required'     => isset($options['requiredIntegrationFields'][$field]),
                    'placeholder'  => $this->translator->trans('mautic.integration.sync_mautic_field'),
                    'attr'         => [
                        'class' => 'form-control',
                    ],
                ]
            );
        }

        $builder->add(
            'filter-page',
            HiddenType::class,
            [
                'label'  => false,
                'mapped' => false,
                'data'   => $options['page']
            ]
        );

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
                    'data-object'      => $options['object'],
                    'data-integration' => $options['integration'],
                ],
            ]
        );

        $builder->add(
            'filter-totalFieldCount',
            HiddenType::class,
            [
                'label'  => false,
                'mapped' => false,
                'data'   => $options['totalFieldCount']
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'requiredIntegrationFields',
                'integrationFields',
                'mauticFields',
                'page',
                'keyword',
                'totalFieldCount',
                'object',
                'integration',
            ]
        );
    }
}