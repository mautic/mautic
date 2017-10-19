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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Model\PluginModel;
use Mautic\PluginBundle\Helper\BuilderHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ThemeListType.
 */
class ThemeListType extends AbstractType
{
    private $factory;
    private $builder;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, BuilderHelper $builder)
    {
        $this->factory = $factory;
        $this->builder = $builder;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $factory = $this->factory;
        $resolver->setDefaults([
            'choices' => function (Options $options) use ($factory) {
                $themes = $factory->getInstalledThemes($options['feature']);
                $themes['mautic_code_mode'] = 'Code Mode';

                $builderPlugins = $this->builder->getBuilderPlugins();

                foreach ($builderPlugins as $name => $config) {
                    $themes[$name] = $config['name'];
                }

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
            'feature' => 'all',
        ]);

        $resolver->setOptional(['feature']);
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
