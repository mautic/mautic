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
use Mautic\LeadBundle\LeadEvents;
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
        // if ('campaign' !== $event->getFieldName()) {
        //     return;
        // }

        $form = $event->getFilterPropertiesForm();

        // Add new default select input.
        $form->add(
            'filter',
            ChoiceType::class,
            [
                'label'                     => false,
                'attr'                      => ['class' => 'form-control'],
                'choices'                   => ['a' => ['b' => 'c']],
                'multiple'                  => true,
                'choice_translation_domain' => false,
            ]
        );

        $form->add(
            'filter:default',
            ChoiceType::class,
            [
                'label'                     => false,
                'attr'                      => ['class' => 'form-control'],
                'choices'                   => ['a', 'b'],
                'multiple'                  => true,
                'choice_translation_domain' => false,
            ]
        );

        $form->add(
            'filter:empty',
            TextType::class,
            [
                'label' => false,
                'attr'  => ['class' => 'form-control', 'disabled' => true],
            ]
        );
    }
}
