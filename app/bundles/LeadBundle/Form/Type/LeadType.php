<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;

/**
 * Class LeadType.
 */
class LeadType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    private $translator;
    private $factory;
    private $companyModel;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, CompanyModel $companyModel)
    {
        $this->translator   = $factory->getTranslator();
        $this->factory      = $factory;
        $this->companyModel = $companyModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormExitSubscriber('lead.lead', $options));

        if (!$options['isShortForm']) {
            $imageChoices = [
                'gravatar' => 'Gravatar',
                'custom'   => 'mautic.lead.lead.field.custom_avatar',
            ];

            $cache = $options['data']->getSocialCache();
            if (count($cache)) {
                foreach ($cache as $key => $data) {
                    $imageChoices[$key] = $key;
                }
            }

            $builder->add(
                'preferred_profile_image',
                'choice',
                [
                    'choices'    => $imageChoices,
                    'label'      => 'mautic.lead.lead.field.preferred_profile',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => true,
                    'multiple'   => false,
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                ]
            );

            $builder->add(
                'custom_avatar',
                'file',
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

        $this->getFormFields($builder, $options);

        $builder->add(
            'tags',
            'lead_tag',
            [
                'by_reference' => false,
                'attr'         => [
                    'data-placeholder'     => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
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
            'company_list',
            [
                'label'      => 'mautic.company.selectcompany',
                'label_attr' => ['class' => 'control-label'],
                'multiple'   => true,
                'required'   => false,
                'mapped'     => false,
                'data'       => $leadCompanies,
            ]
        );

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
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

        $transformer = new IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticStageBundle:Stage'
        );

        $builder->add(
            $builder->create(
                'stage',
                'stage_list',
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
            $builder->add('buttons', 'form_buttons');
        } else {
            $builder->add(
                'buttons',
                'form_buttons',
                [
                    'apply_text' => false,
                    'save_text'  => 'mautic.core.form.save',
                ]
            );
        }

        $builder->addEventSubscriber(new CleanFormSubscriber(['clean', 'raw']));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'  => 'Mautic\LeadBundle\Entity\Lead',
                'isShortForm' => false,
            ]
        );

        $resolver->setRequired(['fields', 'isShortForm']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead';
    }
}
