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
use Mautic\ConfigBundle\Form\Type\ConfigType as ConfigParentType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ConfigType
 *
 * @package Mautic\PageBundle\Form\Type
 */
class ConfigType extends ConfigParentType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('cat_in_page_url', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.page.config.form.cat.in.url',
            'expanded'    => true,
            'empty_value' => false,
            'data'        => (bool) $options['data']['cat_in_page_url']
        ));

        $builder->add('google_analytics', 'text', array(
            'label'      => 'mautic.page.config.form.google.analytics',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pageconfig';
    }
}