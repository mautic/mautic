<?php

namespace Mautic\DynamicContentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class DynamicContentSendType extends AbstractType
{
    public function __construct(
        protected RouterInterface $router,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'dynamicContent',
            DynamicContentListType::class,
            [
                'label'      => 'mautic.dynamicContent.send.selectDynamicContents',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.dynamicContent.choose.dynamicContents',
                    'onchange' => 'Mautic.disabledDynamicContentAction()',
                ],
                'where'       => 'e.isCampaignBased = 1', // do not show dwc with filters
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(['message' => 'mautic.core.value.required']),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_dynamicContent_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newDynamicContentButton',
                ButtonType::class,
                [
                    'label' => 'mautic.dynamicContent.send.new.dynamicContent',
                    'attr'  => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                            "windowUrl": "'.$windowUrl.'"
                        })',
                        'icon' => 'ri-add-line',
                    ],
                ]
            );

            $dynamicContent = is_array($options['data']) && array_key_exists('dynamicContent', $options['data']) ? $options['data']['dynamicContent']
                : null;

            // create button edit notification
            $windowUrlEdit = $this->router->generate(
                'mautic_dynamicContent_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'dynamicContentId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editDynamicContentButton',
                ButtonType::class,
                [
                    'label' => 'mautic.dynamicContent.send.edit.dynamicContent',
                    'attr'  => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardDynamicContentUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($dynamicContent),
                        'icon'     => 'ri-edit-line',
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['update_select']);
    }

    public function getBlockPrefix(): string
    {
        return 'dwcsend_list';
    }
}
