<?php

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FormSubmitActionDownloadFileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'asset',
            AssetListType::class,
            [
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.asset.form.submit.assets',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => 'mautic.asset.form.submit.latest.category',
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.form.submit.assets_descr',
                ],
            ]
        );

        $builder->add(
            'category',
            CategoryListType::class,
            [
                'label'         => 'mautic.asset.form.submit.latest.category',
                'label_attr'    => ['class' => 'control-label'],
                'placeholder'   => false,
                'required'      => false,
                'bundle'        => 'asset',
                'return_entity' => false,
                'attr'          => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.asset.form.submit.latest.category_descr',
                    'data-show-on' => '{"formaction_properties_asset":""}',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'asset_submitaction_downloadfile';
    }
}
