<?php

namespace Mautic\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
class NotificationSendType extends AbstractType
{
    public function __construct(
        protected RouterInterface $router,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'notification',
            NotificationListType::class,
            [
                'label'      => 'mautic.notification.send.selectnotifications',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.notification.choose.notifications',
                    'onchange' => 'Mautic.disabledNotificationAction()',
                ],
                'multiple'    => false,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.notification.choosenotification.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate('mautic_notification_action', [
                'objectAction' => 'new',
                'contentOnly'  => 1,
                'updateSelect' => $options['update_select'],
            ]);

            $builder->add(
                'newNotificationButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                            "windowUrl": "'.$windowUrl.'"
                        })',
                        'icon' => 'ri-add-line',
                    ],
                    'label' => 'mautic.notification.send.new.notification',
                ]
            );

            if (array_key_exists('data', $options)) {
                if (is_array($options['data']) && array_key_exists('notification', $options['data'])) {
                    $notification = $options['data']['notification'];
                }
            }

            // create button edit notification
            $windowUrlEdit = $this->router->generate('mautic_notification_action', [
                'objectAction' => 'edit',
                'objectId'     => 'notificationId',
                'contentOnly'  => 1,
                'updateSelect' => $options['update_select'],
            ]);

            $builder->add(
                'editNotificationButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardNotificationUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($notification),
                        'icon'     => 'ri-edit-line',
                    ],
                    'label' => 'mautic.notification.send.edit.notification',
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
        return 'notificationsend_list';
    }
}
