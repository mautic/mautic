<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;

/**
 * Class ConfigType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('upload_dir', 'text', array(
            'label'       => 'mautic.asset.config.form.upload.dir',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.asset.config.form.upload.dir.tooltip'
                ),
            'constraints' => array(
                new NotBlank(array(
                    'message' => 'mautic.core.value.required'
                ))
            )
        ));

        $builder->add('max_size', 'text', array(
            'label'       => 'mautic.asset.config.form.max.size',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.asset.config.form.max.size.tooltip'
                ),
            'constraints' => array(
                new NotBlank(array(
                    'message' => 'mautic.core.value.required'
                ))
            )
        ));

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create('allowed_extensions', 'text', array(
                'label'      => 'mautic.asset.config.form.allowed.extensions',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.asset.config.form.allowed.extensions.tooltip'
                ),
                'required'   => false
            ))->addViewTransformer($arrayStringTransformer)
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