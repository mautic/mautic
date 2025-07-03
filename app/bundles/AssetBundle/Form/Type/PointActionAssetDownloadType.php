<?php

namespace Mautic\AssetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class PointActionAssetDownloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'assets',
            AssetListType::class,
            [
                'expanded'    => false,
                'multiple'    => true,
                'label'       => 'mautic.asset.point.action.assets',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.point.action.assets.descr',
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'pointaction_assetdownload';
    }
}
