<?php

namespace Mautic\NotificationBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\Form\Type\EmailUtmTagsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NotificationType.
 */
class NotificationType extends AbstractType
{
    const PROPERTY_ALLOWED_FILE_EXTENSIONS = 'png,gif';

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var NotificationUploader
     */
    protected $notificationUploader;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @param TranslatorInterface  $translator
     * @param NotificationUploader $notificationUploader
     * @param NotificationModel    $notificationModel
     */
    public function __construct(TranslatorInterface $translator, NotificationUploader $notificationUploader, NotificationModel $notificationModel)
    {
        $this->translator           = $translator;
        $this->notificationUploader = $notificationUploader;
        $this->notificationModel    = $notificationModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new FormExitSubscriber('notification.notification', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.notification.form.internal.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.notification.form.internal.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'utmTags',
            EmailUtmTagsType::class,
            [
                'label'      => 'mautic.email.utm_tags',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.utm_tags.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'heading',
            TextType::class,
            [
                'label'       => 'mautic.notification.form.heading',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $builder->add(
            'message',
            TextareaType::class,
            [
                'label'      => 'mautic.notification.form.message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'rows'  => 6,
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $builder->add(
            'url',
            UrlType::class,
            [
                'label'      => 'mautic.notification.form.url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.url.tooltip',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $builder->add(
            'actionButtonUrl1',
            'url',
            [
                'label'      => 'mautic.notification.form.button.url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'  => 'form-control',
                    'tooltip'=> 'mautic.notification.form.button.url.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'actionButtonUrl2',
            'url',
            [
                'label'      => 'mautic.notification.form.button.url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'  => 'form-control',
                    'tooltip'=> 'mautic.notification.form.button.url.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'button',
            TextType::class,
            [
                'label'      => 'mautic.notification.form.button.text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.button.text.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'actionButtonText2',
            'text',
            [
                'label'      => 'mautic.notification.form.button.text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.button.text.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'actionButtonIcon1',
            'file',
            [
                'label'      => 'mautic.notification.form.button.icon',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.button.icon.tooltip',
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

        $fileName = '';
        if ($options['data']->getId()) {
            $notification =  $this->notificationModel->getEntity($options['data']->getId());
            $fileName     = $notification->getActionButtonIcon1();
        }
        $builder->add(
            'actionButtonIcon1_delete',
            CheckboxType::class,
            [
                'label'      => $this->translator->trans('mautic.notification.form.delete', ['%url%'=> $this->notificationUploader->getFullUrl($options['data'], 'actionButtonIcon1'), '%file%'=>$fileName]),
                'label_attr' => ['class' => 'control-label'],
                'mapped'     => false,
                'data'       => false,
            ]
        );

        $builder->add(
            'actionButtonIcon2',
            FileType::class,
            [
                'label'      => 'mautic.notification.form.button.icon',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.button.icon.tooltip',
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
        $builder->add(
            'actionButtonIcon2_delete',
            CheckboxType::class,
            [
                'label'      => $this->translator->trans('mautic.notification.form.delete', ['%url%'=> $this->notificationUploader->getFullUrl($options['data'], 'actionButtonIcon2'), '%file%'=>$options['data']->getActionButtonIcon2()]),
                'label_attr' => ['class' => 'control-label'],
                'mapped'     => false,
                'data'       => false,
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');
        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'publishUp',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add(
            'publishDown',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        //add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'notification',
            ]
        );

        $builder->add(
            'language',
            LocaleType::class,
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'priority',
            ChoiceType::class,
            [
                'choices'     => $this->getRangeChoices(1, 10),
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.notification.form.priority',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.priority.tooltip',
                ],
            ]
        );

        $builder->add(
            'ttl',
            ChoiceType::class,
            [
                'choices'     => $this->getRangeChoices(1, 72),
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.notification.form.time.to.live',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.time.to.live.tooltip',
                ],
            ]
        );

        $builder->add(
            'icon',
            'file',
            [
                'label'      => 'mautic.notification.form.icon',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.icon.tooltip',
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
        $builder->add(
            'icon_delete',
            CheckboxType::class,
            [
                'label'      => $this->translator->trans('mautic.notification.form.delete', ['%url%'=> $this->notificationUploader->getFullUrl($options['data'], 'icon'), '%file%'=>$options['data']->getIcon()]),
                'label_attr' => ['class' => 'control-label'],
                'mapped'     => false,
                'data'       => false,
            ]
        );

        $builder->add(
            'image',
            'file',
            [
                'label'      => 'mautic.notification.form.image',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.image.tooltip',
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
        $builder->add(
            'image_delete',
            CheckboxType::class,
            [
                'label'      => $this->translator->trans('mautic.notification.form.delete', ['%url%'=> $this->notificationUploader->getFullUrl($options['data'], 'image'), '%file%'=>$options['data']->getImage()]),
                'label_attr' => ['class' => 'control-label'],
                'mapped'     => false,
                'data'       => false,
            ]
        );

        $builder->add('buttons', FormButtonsType::class);

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text' => false,
                ]
            );
            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param int $min
     * @param int $max
     *
     * @return array
     */
    private function getRangeChoices($min, $max)
    {
        $choices = [];
        for ($i = $min; $i <= $max; ++$i) {
            $choices[$i] = $i;
        }

        return $choices;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\NotificationBundle\Entity\Notification',
            ]
        );

        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'notification';
    }
}
