<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\ConfigBundle\Form\Type\DsnType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\Validator\Dsn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class ConfigType extends AbstractType
{
    public const MINIFY_EMAIL_HTML = 'minify_email_html';

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(
            new CleanFormSubscriber(
                [
                    'mailer_from_email'      => 'email',
                    'mailer_return_path'     => 'email',
                    'default_signature_text' => 'html',
                    'unsubscribe_text'       => 'html',
                    'unsubscribe_message'    => 'html',
                    'resubscribe_message'    => 'html',
                    'webview_text'           => 'html',
                    // Encode special chars to keep congruent with Email entity custom headers
                    'mailer_custom_headers'  => 'clean',
                ]
            )
        );

        $builder->add(
            'unsubscribe_text',
            TextareaType::class,
            [
                'label'      => 'mautic.email.config.unsubscribe_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_text.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_text', $options['data']) && !empty($options['data']['unsubscribe_text']))
                    ? $options['data']['unsubscribe_text']
                    : $this->translator->trans(
                        'mautic.email.unsubscribe.text',
                        ['%link%' => '|URL|']
                    ),
            ]
        );

        $builder->add(
            'webview_text',
            TextareaType::class,
            [
                'label'      => 'mautic.email.config.webview_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.webview_text.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('webview_text', $options['data']) && !empty($options['data']['webview_text']))
                    ? $options['data']['webview_text']
                    : $this->translator->trans(
                        'mautic.email.webview.text',
                        ['%link%' => '|URL|']
                    ),
            ]
        );

        $builder->add(
            'unsubscribe_message',
            TextareaType::class,
            [
                'label'      => 'mautic.email.config.unsubscribe_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_message.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_message', $options['data']) && !empty($options['data']['unsubscribe_message']))
                    ? $options['data']['unsubscribe_message']
                    : $this->translator->trans(
                        'mautic.email.unsubscribed.success',
                        [
                            '%resubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'resubscribe_message',
            TextareaType::class,
            [
                'label'      => 'mautic.email.config.resubscribe_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.resubscribe_message.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('resubscribe_message', $options['data']) && !empty($options['data']['resubscribe_message']))
                    ? $options['data']['resubscribe_message']
                    : $this->translator->trans(
                        'mautic.email.resubscribed.success',
                        [
                            '%unsubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'default_signature_text',
            TextareaType::class,
            [
                'label'      => 'mautic.email.config.default_signature_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.default_signature_text.tooltip',
                ],
                'required'   => false,
                'data'       => (!empty($options['data']['default_signature_text']))
                    ? $options['data']['default_signature_text']
                    : $this->translator->trans(
                        'mautic.email.default.signature',
                        [
                            '%from_name%' => '|FROM_NAME|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'mailer_from_name',
            TextType::class,
            [
                'label'       => 'mautic.email.config.mailer.from.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.from.name.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_from_email',
            TextType::class,
            [
                'label'       => 'mautic.email.config.mailer.from.email',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.from.email.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.email.required',
                        ]
                    ),
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                            'mode'    => Email::VALIDATION_MODE_HTML5,
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_reply_to_email',
            TextType::class,
            [
                'label'       => 'mautic.email.reply_to_email',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.reply_to_email.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'required'    => false,
                'constraints' => [
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                            'mode'    => Email::VALIDATION_MODE_HTML5,
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_reply_to_email',
            TextType::class,
            [
                'label'       => 'mautic.email.reply_to_email',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.reply_to_email.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'required'    => false,
                'constraints' => [
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_return_path',
            TextType::class,
            [
                'label'      => 'mautic.email.config.mailer.return.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.return.path.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_address_length_limit',
            NumberType::class,
            [
                'scale'      => 0,
                'label'      => 'mautic.email.config.mailer.address.length.limit',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.address.length.limit.tooltip',
                ],
                'required'   => true,
            ]
        );

        $builder->add(
            'mailer_dsn',
            DsnType::class,
            [
                'constraints' => [new Dsn()],
                'test_button' => [
                    'action' => 'email:sendTestEmail',
                    'label'  => $this->translator->trans('mautic.email.config.mailer.transport.test_send'),
                ],
            ]
        );

        $builder->add(
            'mailer_convert_embed_images',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.mailer.convert.embed.images',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.convert.embed.images.tooltip',
                ],
                'data'       => empty($options['data']['mailer_convert_embed_images']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_append_tracking_pixel',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.mailer.append.tracking.pixel',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.append.tracking.pixel.tooltip',
                ],
                'data'       => empty($options['data']['mailer_append_tracking_pixel']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'disable_trackable_urls',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.mailer.disable.trackable.urls',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.disable.trackable.urls.tooltip',
                ],
                'data'       => empty($options['data']['disable_trackable_urls']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            self::MINIFY_EMAIL_HTML,
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.mailer.minify.html',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.minify.html.tooltip',
                ],
                'data'       => $options['data'][self::MINIFY_EMAIL_HTML] ?? false,
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_custom_headers',
            SortableListType::class,
            [
                'required'        => false,
                'label'           => 'mautic.email.custom_headers',
                'attr'            => [
                    'tooltip'  => 'mautic.email.custom_headers.config.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton(this)',
                ],
                'option_required' => false,
                'with_labels'     => true,
                'key_value_pairs' => true, // do not store under a `list` key and use label as the key
            ]
        );

        $builder->add(
            'monitored_email',
            ConfigMonitoredEmailType::class,
            [
                'label'    => false,
                'data'     => (array_key_exists('monitored_email', $options['data'])) ? $options['data']['monitored_email'] : [],
                'required' => false,
            ]
        );

        $builder->add(
            'mailer_is_owner',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.mailer.is.owner',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.is.owner.tooltip',
                ],
                'data'       => empty($options['data']['mailer_is_owner']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'email_frequency_number',
            NumberType::class,
            [
                'scale'      => 0,
                'label'      => 'mautic.lead.list.frequency.number',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );
        $builder->add(
            'email_frequency_time',
            ChoiceType::class,
            [
                'choices'           => [
                    'day'   => 'DAY',
                    'week'  => 'WEEK',
                    'month' => 'MONTH',
                ],
                'label'      => 'mautic.lead.list.frequency.times',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );
        $builder->add(
            'show_contact_segments',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.contact.segments',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.segments.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_segments']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_preferences',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.preference.options',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.preference.options.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_preferences']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_frequency',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.contact.frequency',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.frequency.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_frequency']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_pause_dates',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.contact.pause.dates',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.pause.dates.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_pause_dates']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_categories',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.contact.categories',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.categories.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_categories']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_preferred_channels',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.show.contact.preferred.channels',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.preferred.channels',
                ],
                'data'       => empty($options['data']['show_contact_preferred_channels']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'email_draft_enabled',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.email.config.enable.draft',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['email_draft_enabled'] ?? false,
                'required'   => false,
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.enable.draft.tooltip',
                ],
            ]
        );
    }

    public function getBlockPrefix(): string
    {
        return 'emailconfig';
    }
}
