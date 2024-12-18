<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\Company;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<Company>
 */
class CompanyType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function __construct(
        private EntityManager $em,
        protected RouterInterface $router,
        protected TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $cleaningRules                 = $this->getFormFields($builder, $options, 'company');
        $cleaningRules['companyemail'] = 'email';

        $transformer = new IdToEntityModelTransformer($this->em, User::class);

        $builder->add(
            $builder->create(
                'owner',
                UserListType::class,
                [
                    'label'      => 'mautic.lead.company.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                    'multiple' => false,
                ]
            )
                ->addModelTransformer($transformer)
        );

        $builder->add(
            'score',
            NumberType::class,
            [
                'label'      => 'mautic.company.score',
                'attr'       => ['class' => 'form-control'],
                'label_attr' => ['class' => 'control-label'],
                'scale'      => 0,
                'required'   => false,
            ]
        );

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text' => false,
                ]
            );

            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }

        if (null === $options['data']->getId()) {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'post_extra_buttons' => [
                        [
                            'name'  => 'merge',
                            'label' => 'mautic.lead.merge',
                            'attr'  => [
                                'class'       => 'btn btn-ghost btn-dnd',
                                'icon'        => 'ri-building-2-line',
                                'data-toggle' => 'ajaxmodal',
                                'data-target' => '#MauticSharedModal',
                                'data-header' => $this->translator->trans('mautic.lead.company.header.merge'),
                                'href'        => $this->router->generate(
                                    'mautic_company_action',
                                    [
                                        'objectId'     => $options['data']->getId(),
                                        'objectAction' => 'merge',
                                    ]
                                ),
                            ],
                        ],
                    ],
                ]
            );
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($cleaningRules));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'    => Company::class,
                'isShortForm'   => false,
                'update_select' => false,
            ]
        );

        $resolver->setRequired(['fields']);
    }
}
