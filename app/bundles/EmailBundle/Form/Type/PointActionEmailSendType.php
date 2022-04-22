<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PointActionEmailSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'emails',
            EmailListType::class,
            [
                'label'=> 'mautic.email.open.limittoemails',
                'attr' => [
                    'tooltip'=> 'mautic.email.open.limittoemails_descr',
                ],
            ]
        );

        $builder->add(
            'categories',
            CategoryListType::class,
            [
                'label'           => 'mautic.email.open.limittocategories',
                'bundle'          => 'email',
                'multiple'        => true,
                'placeholder'     => true,
                'with_create_new' => false,
                'return_entity'   => false,
                'attr'            => [
                    'tooltip'=> 'mautic.email.open.limittocategories_descr',
                ],
            ]
        );
    }
}
