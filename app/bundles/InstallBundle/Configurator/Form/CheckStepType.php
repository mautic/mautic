<?php

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class CheckStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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

        $builder->add('site_url', HiddenType::class);
        $builder->add('cache_path', HiddenType::class);
        $builder->add('log_path', HiddenType::class);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'install_check_step';
    }
}
