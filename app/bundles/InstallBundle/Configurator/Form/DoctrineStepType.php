<?php

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Doctrine Form Type.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @note   This class is based on Sensio\Bundle\DistributionBundle\Configurator\Form\DoctrineStepType
 *
 * @extends AbstractType<mixed>
 */
class DoctrineStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'driver',
            ChoiceType::class,
            [
                'choices'           => array_flip(DoctrineStep::getDrivers()),
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.install.form.database.driver',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'required'          => true,
                'attr'              => [
                    'class' => 'form-control',
                ],
                'constraints'       => [
                    new Choice(
                        [
                            'callback' => '\Mautic\InstallBundle\Configurator\Step\DoctrineStep::getDriverKeys',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'host',
            TextType::class,
            [
                'label'      => 'mautic.install.form.database.host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'port',
            TextType::class,
            [
                'label'      => 'mautic.install.form.database.port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.install.form.database.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'table_prefix',
            TextType::class,
            [
                'label'      => 'mautic.install.form.database.table.prefix',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'user',
            TextType::class,
            [
                'label'      => 'mautic.install.form.database.user',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label'      => 'mautic.install.form.database.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-lock-fill',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'backup_tables',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.install.form.existing_tables',
                'attr'  => [
                    'tooltip'  => 'mautic.install.form.existing_tables_descr',
                    'onchange' => 'MauticInstaller.toggleBackupPrefix();',
                ],
            ]
        );

        $builder->add(
            'backup_prefix',
            TextType::class,
            [
                'label'      => 'mautic.install.form.backup_prefix',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'pre_extra_buttons' => [
                    [
                        'name'  => 'next',
                        'label' => 'mautic.install.next.step',
                        'type'  => 'submit',
                        'attr'  => [
                            'class'   => 'btn btn-success pull-right btn-next',
                            'icon'    => 'ri-arrow-right-circle-line',
                            'onclick' => 'MauticInstaller.showWaitMessage(event);',
                        ],
                    ],
                ],
                'apply_text'  => '',
                'save_text'   => '',
                'cancel_text' => '',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'install_doctrine_step';
    }
}
