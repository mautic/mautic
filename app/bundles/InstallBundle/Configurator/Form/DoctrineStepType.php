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

use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Doctrine Form Type.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DoctrineStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('driver', 'choice', array(
            'choices'       => DoctrineStep::getDrivers(),
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.install.install.form.driver',
            'label_attr'    => array('class' => 'control-label'),
            'empty_value'   => false,
            'required'      => true,
            'attr'          => array(
                'class'    => 'form-control'
            )
        ));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.install.install.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('host', 'text', array(
            'label'      => 'mautic.install.install.form.host',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('path', 'text', array(
            'label'      => 'mautic.install.install.form.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('port', 'text', array(
            'label'      => 'mautic.install.install.form.port',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('user', 'text', array(
            'label'      => 'mautic.install.install.form.user',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('password', 'password', array(
            'label'      => 'mautic.install.install.form.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));
    }

    public function getName()
    {
        return 'distributionbundle_doctrine_step';
    }
}
