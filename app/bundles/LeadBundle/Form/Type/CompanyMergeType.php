<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class CompanyMergeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'company_to_merge',
            CompanyListType::class,
            [
                'multiple'            => false,
                'label'               => 'mautic.company.to.merge.into',
                'required'            => true,
                'modal_route'         => false,
                'main_entity'         => $options['main_entity'],
                'model_lookup_method' => $options['model_lookup_method'],
                'constraints'         => [
                    new NotBlank(
                        ['message' => 'mautic.company.choosecompany.notblank']
                    ),
                ],
            ]
        );
        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text' => false,
                'save_text'  => 'mautic.lead.merge',
                'save_icon'  => 'ri-building-2-line',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(
            ['main_entity', 'model_lookup_method']
        );
    }
}
