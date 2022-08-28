<?php

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class PermissionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'permissions';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'permissionsConfig' => [],
            'constraints'       => [new Valid()],
        ]);
    }
}
