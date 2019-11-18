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

use Mautic\LeadBundle\Form\Type\FilterPropertiesType;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Provider\TypeOperatorProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FilterType extends AbstractType
{
    use FilterTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TypeOperatorProviderInterface
     */
    private $typeOperatorProvider;

    /**
     * @var ListModel
     */
    private $listModel;

    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack,
        TypeOperatorProviderInterface $typeOperatorProvider,
        ListModel $listModel
    ) {
        $this->translator           = $translator;
        $this->requestStack         = $requestStack;
        $this->typeOperatorProvider = $typeOperatorProvider;
        $this->listModel            = $listModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldChoices = $this->listModel->getChoiceFields();

        $builder->add(
            'glue',
            ChoiceType::class,
            [
                'label'             => false,
                'choices'           => [
                    'mautic.lead.list.form.glue.and' => 'and',
                    'mautic.lead.list.form.glue.or'  => 'or',
                ],
                'attr' => [
                    'class'    => 'form-control not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)',
                ],
            ]
        );

        $formModifier = function (FormEvent $event) use ($fieldChoices) {
            // $segmentId = $this->requestStack->getCurrentRequest()->attributes->get('objectId', false);
            $data        = $event->getData();
            $form        = $event->getForm();
            $fieldAlias  = $data['field'];
            $fieldObject = isset($data['object']) ? $data['object'] : 'behaviors';
            $field       = isset($fieldChoices[$fieldObject][$fieldAlias]) ? $fieldChoices[$fieldObject][$fieldAlias] : [];
            $operators   = $field['operators'] ?? [];
            $operator    = isset($data['operator']) ? $data['operator'] : null;

            if ($operators && !$operator) {
                $operator = $this->getFirstOperatorKey($operators);
            }

            $form->add(
                'operator',
                ChoiceType::class,
                [
                    'label'   => false,
                    'choices' => $operators,
                    'attr'    => [
                        'class'    => 'form-control not-chosen',
                        'onchange' => 'Mautic.convertLeadFilterInput(this)',
                    ],
                ]
            );

            $form->add(
                'filter',
                TextType::class,
                [
                    'label' => false,
                    'data'  => isset($data['filter']) ? $data['filter'] : '',
                    'attr'  => ['class' => 'form-control'],
                ]
            );

            $form->add(
                'properties',
                FilterPropertiesType::class,
                [
                    'label' => false,
                ]
            );

            $form->add(
                'display',
                HiddenType::class,
                [
                    'label' => false,
                    'attr'  => [],
                    'data'  => (isset($data['display'])) ? $data['display'] : '',
                ]
            );

            if ($fieldAlias && $operator) {
                $this->typeOperatorProvider->adjustFilterPropertiesType(
                    $form->get('properties'),
                    $fieldAlias,
                    $fieldObject,
                    $operator,
                    $field
                );
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SET_DATA);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SUBMIT);
            }
        );

        $builder->add('field', HiddenType::class);
        $builder->add('object', HiddenType::class);
        $builder->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'deviceBrands',
                'deviceOs',
                'assets',
                'tags',
                'stage',
                'globalcategory',
            ]
        );

        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields'] = $this->listModel->getChoiceFields();
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_filter';
    }

    /**
     * @deprecated replace with native array_key_first() once supported
     */
    private function getFirstOperatorKey(array $operators): string
    {
        foreach ($operators as $key => $value) {
            return $key;
        }
    }
}
