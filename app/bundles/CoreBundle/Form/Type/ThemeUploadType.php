<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class ThemeUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'file',
            FileType::class,
            [
                'attr' => [
                    'accept'   => '.zip',
                    'class'    => 'form-control',
                    'required' => true,
                ],
            ]
        );

        $builder->add(
            'start',
            SubmitType::class,
            [
                'attr'  => [
                    'class'   => 'btn btn-primary btn-sm',
                    'icon'    => 'ri-upload-line',
                    'onclick' => "mQuery(this).prop('disabled', true); mQuery('form[name=\'theme_upload\']').submit();",
                ],
                'label' => 'mautic.core.theme.install',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
