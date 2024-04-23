<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\ReportBundle\Form\DataTransformer\ReportFilterDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class ReportFiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new ReportFilterDataTransformer($options['filters'])
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'filters' => [],
                'report'  => null,
            ]
        );
    }
}
