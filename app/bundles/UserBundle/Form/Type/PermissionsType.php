<?php

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class PermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['permissionsConfig'] as $bundle => $config) {
            $builder->add(
                $bundle,
                HiddenType::class,
                [
                    'data'   => 'newbundle',
                    'label'  => false,
                    'mapped' => false,
                ]
            );
            $config['permissionObject']->buildForm($builder, $options, $config['data']);
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'permissionsConfig' => [],
            'constraints'       => [new Valid()],
        ]);
    }
}
