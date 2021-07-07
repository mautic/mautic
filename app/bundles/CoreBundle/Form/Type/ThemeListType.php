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

use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ThemeListType.
 */
class ThemeListType extends AbstractType
{
    /**
     * @var ThemeHelperInterface
     */
    private $themeHelper;

    /**
     * ThemeListType constructor.
     */
    public function __construct(ThemeHelperInterface $helper)
    {
        $this->themeHelper = $helper;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices'           => function (Options $options) {
                    $themes                     = $this->themeHelper->getInstalledThemes($options['feature']);
                    $themes['mautic_code_mode'] = 'Code Mode';

                    return array_flip($themes);
                },
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.core.form.theme',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'required'          => false,
                'attr'              => [
                    'class' => 'form-control',
                ],
                'feature'           => 'all',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'theme_list';
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
