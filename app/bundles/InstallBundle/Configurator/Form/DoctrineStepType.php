<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Doctrine Form Type.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @note   This class is based on Sensio\Bundle\DistributionBundle\Configurator\Form\DoctrineStepType
 */
class DoctrineStepType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('driver', 'choice', array(
            'choices'     => DoctrineStep::getDrivers(),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.install.form.database.driver',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => true,
            'attr'        => array(
                'class'    => 'form-control',
                'onchange' => 'MauticInstaller.toggleDatabaseSettings(this.value);'
            ),
            'constraints' => array(
                new Choice(
                    array(
                        'callback' => '\Mautic\InstallBundle\Configurator\Step\DoctrineStep::getDriverKeys'
                    )
                )
            )
        ));

        $builder->add('host', 'text', array(
            'label'      => 'mautic.install.form.database.host',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('port', 'text', array(
            'label'      => 'mautic.install.form.database.port',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.install.form.database.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('table_prefix', 'text', array(
            'label'      => 'mautic.install.form.database.table.prefix',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('user', 'text', array(
            'label'      => 'mautic.install.form.database.user',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('password', 'password', array(
            'label'      => 'mautic.install.form.database.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon' => 'fa fa-lock'
            ),
            'required'   => false
        ));

        $builder->add('path', 'text', array(
            'label'      => 'mautic.install.form.database.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.install.form.database.path.tooltip'
            ),
            'required'   => true
        ));

        $builder->add('backup_tables', 'yesno_button_group', array(
            'label'       => 'mautic.install.form.existing_tables',
            'attr'        => array(
                'tooltip' => 'mautic.install.form.existing_tables_descr',
                'onchange' => 'MauticInstaller.toggleBackupPrefix();'
            )
        ));

        $builder->add('backup_prefix', 'text', array(
            'label'      => 'mautic.install.form.backup_prefix',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control'
            ),
            'required'   => false
        ));

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'next',
                    'label' => 'mautic.install.next.step',
                    'type'  => 'submit',
                    'attr'  => array(
                        'class' => 'btn btn-success pull-right btn-next',
                        'icon'  => 'fa fa-arrow-circle-right',
                        'onclick' => 'MauticInstaller.showWaitMessage(event);'
                    )
                )
            ),
            'apply_text'        => '',
            'save_text'         => '',
            'cancel_text'       => ''
        ));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'install_doctrine_step';
    }
}
