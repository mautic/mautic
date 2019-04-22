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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class LeadImportFieldType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(TranslatorInterface $translator, EntityManager $entityManager)
    {
        $this->translator    = $translator;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $specialFields = [
            'mautic.lead.import.label.dateAdded'      => 'dateAdded',
            'mautic.lead.import.label.createdByUser'  => 'createdByUser',
            'mautic.lead.import.label.dateModified'   => 'dateModified',
            'mautic.lead.import.label.modifiedByUser' => 'modifiedByUser',
            'mautic.lead.import.label.lastActive'     => 'lastActive',
            'mautic.lead.import.label.dateIdentified' => 'dateIdentified',
            'mautic.lead.import.label.ip'             => 'ip',
            'mautic.lead.import.label.points'         => 'points',
            'mautic.lead.import.label.stage'          => 'stage',
            'mautic.lead.import.label.doNotEmail'     => 'doNotEmail',
            'mautic.lead.import.label.ownerusername'  => 'ownerusername',
        ];

        $importChoiceFields = [
            'mautic.lead.contact'        => array_flip($options['lead_fields']),
            'mautic.lead.company'        => array_flip($options['company_fields']),
            'mautic.lead.special_fields' => $specialFields,
        ];

        if ('lead' !== $options['object']) {
            unset($importChoiceFields['mautic.lead.contact']);
        }

        foreach ($options['import_fields'] as $field => $label) {
            $builder->add(
                $field,
                ChoiceType::class,
                [
                    'choices'           => $importChoiceFields,
                    'label'             => $label,
                    'required'          => false,
                    'label_attr'        => ['class' => 'control-label'],
                    'attr'              => ['class' => 'form-control'],
                    'data'              => $this->getDefaultValue($field, $options['import_fields']),
                ]
            );
        }

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

        if ('lead' === $options['object']) {
            $builder->add(
                $builder->create(
                    'list',
                    LeadListType::class,
                    [
                        'label'      => 'mautic.lead.lead.field.list',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required' => false,
                        'multiple' => false,
                    ]
                )
            );

            $builder->add(
                'tags',
                TagType::class,
                [
                    'label'      => 'mautic.lead.tags',
                    'required'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'                => 'form-control',
                        'data-placeholder'     => $this->translator->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->translator->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                ]
            );

            $builder->add(
                'skip_if_exists',
                'yesno_button_group',
                [
                    'label'       => 'mautic.lead.import.skip_if_exists',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => ['class' => 'form-control'],
                    'required'    => false,
                    'data'        => false,
                ]
            );
        }

        $buttons = ['cancel_icon' => 'fa fa-times'];

        if (empty($options['line_count_limit'])) {
            $buttons = array_merge(
                $buttons,
                [
                    'apply_text'  => 'mautic.lead.import.in.background',
                    'apply_class' => 'btn btn-success',
                    'apply_icon'  => 'fa fa-history',
                    'save_text'   => 'mautic.lead.import.start',
                    'save_class'  => 'btn btn-primary',
                    'save_icon'   => 'fa fa-upload',
                ]
            );
        } else {
            $buttons = array_merge(
                $buttons,
                [
                    'apply_text' => false,
                    'save_text'  => 'mautic.lead.import',
                    'save_class' => 'btn btn-primary',
                    'save_icon'  => 'fa fa-upload',
                ]
            );
        }

        $builder->add('buttons', FormButtonsType::class, $buttons);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['lead_fields', 'import_fields', 'company_fields', 'object']);
        $resolver->setDefaults(['line_count_limit' => 0]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_field_import';
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    public function getDefaultValue($fieldName, array $importFields)
    {
        if (isset($importFields[$fieldName])) {
            return $importFields[$fieldName];
        }

        return null;
    }
}
