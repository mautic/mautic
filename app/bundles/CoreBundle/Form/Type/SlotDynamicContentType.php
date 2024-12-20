<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

class SlotDynamicContentType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function getBlockPrefix(): string
    {
        return 'slot_dynamiccontent';
    }
}
