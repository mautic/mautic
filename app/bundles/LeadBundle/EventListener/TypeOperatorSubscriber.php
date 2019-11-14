<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\FilterPropertiesTypeEvent;
use Mautic\LeadBundle\Event\ListFieldChoicesEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TypeOperatorSubscriber extends CommonSubscriber
{
    use OperatorListTrait;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE           => ['onTypeOperatorsCollect', 0],
            LeadEvents::COLLECT_FILTER_CHOICES_FOR_LIST_FIELD_TYPE => ['onTypeListCollect', 0],
            LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD          => ['onSegmentFilterForm', 0],
        ];
    }

    public function onTypeOperatorsCollect(TypeOperatorsEvent $event)
    {
        // Subscribe basic field types.
        foreach ($this->typeOperators as $typeName => $operatorOptions) {
            $event->setOperatorsForFieldType($typeName, $operatorOptions);
        }

        // Subscribe aliases
        $event->setOperatorsForFieldType('boolean', $this->typeOperators['bool']);
        $event->setOperatorsForFieldType('datetime', $this->typeOperators['date']);

        foreach (['country', 'timezone', 'region', 'locale'] as $selectAlias) {
            $event->setOperatorsForFieldType($selectAlias, $this->typeOperators['select']);
        }

        foreach (['lookup', 'text', 'email', 'url', 'email', 'tel', 'number'] as $textAlias) {
            $event->setOperatorsForFieldType($textAlias, $this->typeOperators['text']);
        }
    }

    public function onTypeListCollect(ListFieldChoicesEvent $event)
    {
        $event->setChoicesForFieldType(
            'boolean',
            [
                0 => $this->translator->trans('mautic.core.form.no'),
                1 => $this->translator->trans('mautic.core.form.yes'),
            ]
        );
    }

    public function onSegmentFilterForm(FilterPropertiesTypeEvent $event)
    {
        $form = $event->getFilterPropertiesForm();
        $data = $form->getData();

        if ($event->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY, OperatorOptions::REGEXP, OperatorOptions::NOT_REGEXP)) {
            $form->add(
                'filter',
                TextType::class,
                [
                    'label' => false,
                    'attr'  => [
                        'class'    => 'form-control',
                        'disabled' => $event->operatorIsOneOf(OperatorOptions::EMPTY, OperatorOptions::NOT_EMPTY),
                    ],
                ]
            );

            return;
        }

        if ($event->fieldTypeIsOneOf('select', 'multiselect', 'boolean')) {
            $multiple = $event->operatorIsOneOf(OperatorOptions::IN, OperatorOptions::NOT_IN);

            // Conversion between select and multiselect values.
            if ($multiple) {
                if (!isset($data['filter'])) {
                    $data['filter'] = [];
                } elseif (!is_array($data['filter'])) {
                    $data['filter'] = [$data['filter']];
                }
            }

            $form->add(
                'filter',
                ChoiceType::class,
                [
                    'label'                     => false,
                    'attr'                      => ['class' => 'form-control'],
                    'data'                      => $data['filter'],
                    'choices'                   => FormFieldHelper::parseList($event->getFieldChoices(), true, ('boolean' === $event->getFieldType())),
                    'multiple'                  => $multiple,
                    'choice_translation_domain' => false,
                ]
            );

            return;
        }

        $form->add(
            'filter',
            TextType::class,
            [
                'label' => false,
                'attr'  => ['class' => 'form-control'],
            ]
        );
    }
}
