<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SortableListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailClickDecisionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'urls',
            SortableListType::class,
            [
                'label'           => 'mautic.email.click.urls.contains',
                'option_required' => false,
                'with_labels'     => false,
                'required'        => false,
            ]
        );
    }
}
