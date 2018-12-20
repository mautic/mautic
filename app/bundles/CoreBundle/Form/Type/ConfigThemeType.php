<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigThemeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'theme',
            'theme_list',
            [
                'label' => 'mautic.core.config.form.theme',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.template.help',
                ],
            ]
        );
        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create('theme_import_allowed_extensions', 'text', [
                'label'      => 'mautic.core.config.form.theme.import.allowed.extensions',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
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
        return 'themeconfig';
    }
}
