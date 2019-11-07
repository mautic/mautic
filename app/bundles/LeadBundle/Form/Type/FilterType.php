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

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator   = $translator;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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

        $formModifier = function (FormEvent $event) {
            $segmentId = $this->requestStack->getCurrentRequest()->attributes->get('objectId', false);
            $data      = $event->getData();
            $form      = $event->getForm();
            $options   = $form->getConfig()->getOptions();
            $fieldName = $data['field'];
            if (isset($options['fields']['behaviors'][$fieldName])) {
                $field = $options['fields']['behaviors'][$fieldName];
            } elseif (isset($data['object']) && isset($options['fields'][$data['object']][$fieldName])) {
                $field = $options['fields'][$data['object']][$fieldName];
            }
            $form->add(
                'operator',
                ChoiceType::class,
                [
                    'label'   => false,
                    'choices' => isset($field['operators']) ? $field['operators'] : [],
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
                'filter:default',
                TextType::class,
                [
                    'label' => false,
                    'data'  => isset($data['filter']) ? $data['filter'] : '',
                    'attr'  => ['class' => 'form-control', 'disabled' => true],
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

            $this->typeOperatorProvider->adjustFilterFormType($event);
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
                'timezones',
                'countries',
                'regions',
                'fields',
                'lists',
                'campaign',
                'emails',
                'deviceTypes',
                'deviceBrands',
                'deviceOs',
                'assets',
                'tags',
                'stage',
                'locales',
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
        $view->vars['fields'] = $options['fields'];
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_filter';
    }
}
