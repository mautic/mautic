<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlideshowGlobalConfigType
 */
class SlideshowGlobalConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('height', 'text', array(
            'label'      => 'mautic.page.slideshow.height',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.slideshow.height.desc',
                'data-slot-config' => $options['data']['slot']
            ),
            'required'   => false,
            'data'      => isset($options['data']['height']) ? $options['data']['height'] : ''
        ));

        $builder->add('width', 'text', array(
            'label'      => 'mautic.page.slideshow.width',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.slideshow.width.desc',
                'data-slot-config' => $options['data']['slot']
            ),
            'required'   => false,
            'data'      => isset($options['data']['width']) ? $options['data']['width'] : ''
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'slideshow_config';
    }
}
