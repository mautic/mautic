<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LookupType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data-toggle' => 'field-lookup',
            'data-action' => 'lead:fieldList',
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }
}
