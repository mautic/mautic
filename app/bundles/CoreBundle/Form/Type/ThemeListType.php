<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ThemeListType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class ThemeListType extends AbstractType
{

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $factory = $this->factory;
        $resolver->setDefaults(array(
            'choices'       => function(Options $options) use ($factory) {
                return $factory->getInstalledThemes($options['feature']);
            },
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.core.form.theme',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control'
            ),
            'feature'       => 'all'
        ));

        $resolver->setOptional(array('feature'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "theme_list";
    }

    public function getParent()
    {
        return "choice";
    }
}
