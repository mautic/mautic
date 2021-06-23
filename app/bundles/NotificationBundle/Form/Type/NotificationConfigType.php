<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;

class NotificationConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'send_notification_to_author',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.notification.form.config.send_notification_to_author',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.notification.form.config.send_notification_to_author.tooltip',
                ],
            ]
        );
        $builder->add(
            'notification_email_addresses',
            TextType::class,
            [
                'label'      => 'mautic.notification.form.config.notification_email_addresses',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.core.optional',
                    'tooltip'      => 'mautic.notification.form.config.notification_email_addresses.tooltip',
                    'data-show-on' => '{"config_notification_config_send_notification_to_author_0":"checked"}',
                ],
                'required'    => false,
                'constraints' => new Email(
                    [
                        'message' => 'mautic.core.email.required',
                    ]
                ),
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'notification_config';
    }
}
