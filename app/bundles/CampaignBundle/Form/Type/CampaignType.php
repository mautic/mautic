<?php

namespace Mautic\CampaignBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CampaignType.
 */
class CampaignType extends AbstractType
{
    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CorePermissions $security, TranslatorInterface $translator)
    {
        $this->security   = $security;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('campaign', $options));

        $builder->add('name', TextType::class, [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        $builder->add('allowRestart',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.campaign.allow_restart',
                'attr'  => [
                    'tooltip' => 'mautic.campaign.allow_restart.tooltip',
                ],
            ]
        );

        //add category
        $builder->add('category', CategoryListType::class, [
            'bundle' => 'campaign',
        ]);

        $attr = [];
        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->isGranted('campaign:campaigns:publish');
            $data     = $options['data']->isPublished(false);
            $attr     = [
                'onchange'              => 'Mautic.showCampaignConfirmation(mQuery(this));',
                'data-toggle'           => 'confirmation',
                'data-message'          => $this->translator->trans('mautic.campaign.form.confirmation.message'),
                'data-confirm-text'     => $this->translator->trans('mautic.campaign.form.confirmation.confirm_text'),
                'data-confirm-callback' => 'dismissConfirmation',
                'data-cancel-text'      => $this->translator->trans('mautic.campaign.form.confirmation.cancel_text'),
                'data-cancel-callback'  => 'setPublishedButtonToYes',
                'class'                 => 'btn btn-default',
            ];
        } elseif (!$this->security->isGranted('campaign:campaigns:publish')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = false;
        }

        $attr['readonly'] = $readonly;

        $builder->add('isPublished', YesNoButtonGroupType::class, [
            'data' => $data,
            'attr' => $attr,
        ]);

        $builder->add('publishUp', DateTimeType::class, [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('publishDown', DateTimeType::class, [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('sessionId', HiddenType::class, [
            'mapped' => false,
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add('buttons', FormButtonsType::class, [
            'pre_extra_buttons' => [
                [
                    'name'  => 'builder',
                    'label' => 'mautic.campaign.campaign.launch.builder',
                    'attr'  => [
                        'class'   => 'btn btn-default btn-dnd',
                        'icon'    => 'fa fa-cube',
                        'onclick' => 'Mautic.launchCampaignEditor();',
                    ],
                ],
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Mautic\CampaignBundle\Entity\Campaign',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'campaign';
    }
}
