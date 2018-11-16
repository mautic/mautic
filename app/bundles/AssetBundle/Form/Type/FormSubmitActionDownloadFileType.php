<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormSubmitActionDownloadFileType.
 */
class FormSubmitActionDownloadFileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('asset', AssetListType::class, [
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.asset.form.submit.assets',
            'label_attr'  => ['class' => 'control-label'],
            'empty_value' => 'mautic.asset.form.submit.latest.category',
            'required'    => false,
            'attr'        => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.form.submit.assets_descr',
            ],
        ]);

        $builder->add('category', CategoryListType::class, [
            'label'         => 'mautic.asset.form.submit.latest.category',
            'label_attr'    => ['class' => 'control-label'],
            'empty_value'   => false,
            'required'      => false,
            'bundle'        => 'asset',
            'return_entity' => false,
            'attr'          => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.asset.form.submit.latest.category_descr',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'asset_submitaction_downloadfile';
    }
}
