<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 *
 * @package Mautic\ApiBundle\Form\Type
 */
class ConfigType extends AbstractType
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
            'data'        => (bool) $options['data']['api_enabled'],
            'required'    => false,
            'attr'        => array(
                'tooltip' => 'mautic.api.config.form.api.enabled.tooltip'
            )
        ));

        $builder->add('api_mode', 'choice', array(
            'choices'  => array(
                'oauth1' => 'mautic.api.config.oauth1',
                'oauth2' => 'mautic.api.config.oauth2'
            ),
            'label'    => 'mautic.api.config.form.api.mode',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.api.config.form.api.mode.tooltip'
            ),
            'empty_value' => false
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