<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Based on Sensio\DistributionBundle
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Secret Form Type.
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class SecretStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('secret', 'text', array(
            'label'      => 'mautic.install.install.form.secret',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'next',
                    'label' => 'mautic.install.next.step',
                    'type'  => 'submit',
                    'attr'  => array(
                        'class'   => 'btn btn-success',
                        'icon'    => 'fa fa-arrow-circle-right padding-sm-right'
                    )
                )
            ),
            'apply_text'  => '',
            'save_text'   => '',
            'cancel_text' => ''
        ));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function getName()
    {
        return 'distributionbundle_secret_step';
    }
}
