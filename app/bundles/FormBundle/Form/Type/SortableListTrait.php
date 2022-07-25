<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait SortableListTrait
{
    /**
     * @param $options
     */
    public function addSortableList(FormBuilderInterface $builder, $options, $listName = 'list', $listData = null, $formName = 'formfield')
    {
        $listOptions = [
            'with_labels' => true,
            'attr'        => [
                'data-show-on' => '{"'.$formName.'_properties_syncList_1": "", "'.$formName.'_leadField:data-list-type": "empty"}',
            ],
            'option_required'     => false,
            'constraint_callback' => new Callback(
                function ($validateMe, ExecutionContextInterface $context) use ($listName) {
                    $data = $context->getRoot()->getData();
                    if ((empty($data['properties']['syncList']) || empty($data['leadField'])) && !count($data['properties'][$listName]['list'])) {
                        $context->buildViolation('mautic.form.lists.count')->addViolation();
                    }
                }
            ),
        ];

        if (null !== $listData) {
            $listOptions['data'] = $listData;
        }

        $builder->add($listName, SortableListType::class, $listOptions);
        $builder->add(
            'syncList',
            YesNoButtonGroupType::class,
            [
                'attr' => [
                    'data-show-on' => '{"'.$formName.'_leadField:data-list-type": "1"}',
                ],
                'label' => 'mautic.form.field.form.property_list_sync_choices',
                'data'  => !isset($options['data']['syncList']) ? false : (bool) $options['data']['syncList'],
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $formData = $event->getForm()->getParent()->getData();
            $form = $event->getForm();
            if (empty($formData['leadField'])) {
                // Disable sync list if a contact field is not mapped
                $data = $event->getData();
                $data['syncList'] = '0';
                $form->setData($data);
            }
        });
    }
}
