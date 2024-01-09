<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Form\Type\StageListType;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<Lead>
 */
class LeadType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function __construct(
        private TranslatorInterface $translator,
        private CompanyModel $companyModel,
        private EntityManager $entityManager
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new FormExitSubscriber('lead.lead', $options));

        if (!$options['isShortForm']) {
            $imageChoices = [
                'Gravatar'                             => 'gravatar',
                'mautic.lead.lead.field.custom_avatar' => 'custom',
            ];

            $cache = $options['data']->getSocialCache();
            if (count($cache)) {
                foreach ($cache as $key => $data) {
                    $imageChoices[$key] = $key;
                }
            }

            $builder->add(
                'preferred_profile_image',
                ChoiceType::class,
                [
                    'choices'           => $imageChoices,
                    'label'             => 'mautic.lead.lead.field.preferred_profile',
                    'label_attr'        => ['class' => 'control-label'],
                    'attr'              => ['class' => 'form-control'],
                    'required'          => true,
                    'multiple'          => false,
                ]
            );

            $builder->add(
                'custom_avatar',
                FileType::class,
                [
                    'label'      => false,
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'mapped'      => false,
                    'constraints' => [
                        new File(
                            [
                                'mimeTypes' => [
                                    'image/gif',
                                    'image/jpeg',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'mautic.lead.avatar.types_invalid',
                            ]
                        ),
                    ],
                ]
            );
        }

        $cleaningRules          = $this->getFormFields($builder, $options);
        $cleaningRules['email'] = 'email';

        $builder->add(
            'tags',
            TagType::class,
            [
                'by_reference' => false,
                'attr'         => [
                    'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)',
                ],
            ]
        );

        $companyLeadRepo = $this->companyModel->getCompanyLeadRepository();
        $companies       = $companyLeadRepo->getCompaniesByLeadId($options['data']->getId());
        $leadCompanies   = [];
        foreach ($companies as $company) {
            $leadCompanies[(string) $company['company_id']] = (string) $company['company_id'];
        }

        $builder->add(
            'companies',
            CompanyListType::class,
            [
                'label'      => 'mautic.company.selectcompany',
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => true,
                'required'   => false,
                'mapped'     => false,
                'data'       => $leadCompanies,
            ]
        );

        $transformer = new IdToEntityModelTransformer($this->entityManager, User::class);

        $builder->add(
            $builder->create(
                'owner',
                UserListType::class,
                [
                    'label'      => 'mautic.lead.lead.field.owner',
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

        $transformer = new IdToEntityModelTransformer($this->entityManager, Stage::class);

        $builder->add(
            $builder->create(
                'stage',
                StageListType::class,
                [
                    'label'      => 'mautic.lead.lead.field.stage',
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

        if (!$options['isShortForm']) {
            $builder->add('buttons', FormButtonsType::class);
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text' => false,
                    'save_text'  => 'mautic.core.form.save',
                ]
            );
        }

        $builder->addEventSubscriber(new CleanFormSubscriber($cleaningRules));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'  => Lead::class,
                'isShortForm' => false,
            ]
        );

        $resolver->setRequired(['fields', 'isShortForm']);
    }
}
