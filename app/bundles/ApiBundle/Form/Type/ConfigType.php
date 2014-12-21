<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

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
 * @package Mautic\ApiBundle\Form\Type
 */
class ConfigType extends ConfigParentType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('api_enabled', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.api.config.form.api.enabled',
            'expanded'    => true,
            'empty_value' => false,
            'data'        => (bool) $options['data']['api_enabled']
        ));

        $builder->add('api_mode', 'choice', array(
            'choices'  => array(
                'oauth1' => 'mautic.api.config.oauth1',
                'oauth2' => 'mautic.api.config.oauth2'
            ),
            'label'    => 'mautic.api.config.form.api.mode',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'apiconfig';
    }
}