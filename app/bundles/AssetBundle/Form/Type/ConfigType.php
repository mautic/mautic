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

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('upload_dir', 'text', [
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
        ]);

        $builder->add('max_size', 'text', [
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
        ]);

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create('allowed_extensions', 'text', [
                'label'      => 'mautic.asset.config.form.allowed.extensions',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.config.form.allowed.extensions.tooltip',
                ],
                'required' => false,
            ])->addViewTransformer($arrayStringTransformer)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assetconfig';
    }
}
