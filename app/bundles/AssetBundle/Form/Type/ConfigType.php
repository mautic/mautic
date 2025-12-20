<?php

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'upload_dir',
            TextType::class,
            [
                'label'      => 'mautic.asset.config.form.upload.dir',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.config.form.upload.dir.tooltip',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
            ]
        );

        $builder->add(
            'max_size',
            TextType::class,
            [
                'label'      => 'mautic.asset.config.form.max.size',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.config.form.max.size.tooltip',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
            ]
        );

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create(
                'allowed_extensions',
                TextType::class,
                [
                    'label'      => 'mautic.asset.config.form.allowed.extensions',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.asset.config.form.allowed.extensions.tooltip',
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayStringTransformer)
        );

        $builder->add(
            'auto_asset_tracking_enabled',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.asset.config.form.auto_tracking.enabled',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip' => 'mautic.asset.config.form.auto_tracking.enabled.tooltip',
                ],
                'data'       => (bool) ($options['data']['auto_asset_tracking_enabled'] ?? false),
            ]
        );

        $builder->add(
            'auto_asset_tracking_category',
            CategoryListType::class,
            [
                'label'      => 'mautic.asset.config.form.auto_tracking.category',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.asset.config.form.auto_tracking.category.tooltip',
                    'data-show-on' => '{"config_assetconfig_auto_asset_tracking_enabled_1":"checked"}',
                ],
                'bundle'     => 'asset',
                'required'   => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'assetconfig';
    }
}
