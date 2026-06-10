<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class ConfigCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'company_unique_identifiers_operator',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.core.config.contact_unique_identifiers_operator.or'    => CompositeExpression::TYPE_OR,
                    'mautic.core.config.contact_unique_identifiers_operator.and'   => CompositeExpression::TYPE_AND,
                ],
                'label'             => 'mautic.core.config.unique_identifiers_operator',
                'required'          => false,
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.unique_identifiers_operator.tooltip',
                ],
                'placeholder'       => false,
            ]
        );

        $formModifier = static function (FormEvent $event): void {
            $data           = $event->getData();
            $currentColumns = \is_array($data) ? ($data['company_columns'] ?? []) : [];
            $order          = '';
            $orderColumns   = [];

            if (!empty($currentColumns) && \is_array($currentColumns)) {
                $orderColumns = array_values($currentColumns);
                $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
            }

            $event->getForm()->add(
                'company_columns',
                CompanyColumnsType::class,
                [
                    'label'       => 'mautic.config.tab.columns',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'         => 'form-control multiselect',
                        'data-sortable' => 'true',
                        'data-order'    => $order,
                    ],
                    'multiple'    => true,
                    'required'    => true,
                    'expanded'    => false,
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.value.required']
                        ),
                    ],
                    'data' => array_flip($orderColumns),
                ]
            );
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $formModifier);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $formModifier);
    }

    public function getBlockPrefix(): string
    {
        return 'companyconfig';
    }
}
