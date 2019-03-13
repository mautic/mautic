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

use Mautic\CoreBundle\Helper\ThemeHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ThemeListType.
 */
class ThemeListType extends AbstractType
{
    /**
     * @var ThemeHelper
     */
    private $themeHelper;

    /**
     * ThemeListType constructor.
     *
     * @param ThemeHelper $helper
     */
    public function __construct(ThemeHelper $helper)
    {
        $this->themeHelper = $helper;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'     => function (Options $options) {
                    $themes                     = $this->themeHelper->getInstalledThemes($options['feature']);
                    $themes['mautic_code_mode'] = 'Code Mode';

                    return $themes;
                },
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.core.form.theme',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'class' => 'form-control',
                ],
                'feature'     => 'all',
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'theme_list';
    }

    public function getParent()
    {
        return 'choice';
    }
}
