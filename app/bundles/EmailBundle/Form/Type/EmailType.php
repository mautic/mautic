<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\DataTransformer\EmojiToShortTransformer;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\DynamicContentTrait;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EmailType.
 */
class EmailType extends AbstractType
{
    use DynamicContentTrait;

    private $translator;
    private $defaultTheme;
    private $em;
    private $request;

    private $countryChoices  = [];
    private $regionChoices   = [];
    private $timezoneChoices = [];
    private $stageChoices    = [];
    private $localeChoices   = [];

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->translator   = $factory->getTranslator();
        $this->defaultTheme = $factory->getParameter('theme');
        $this->em           = $factory->getEntityManager();
        $this->request      = $factory->getRequest();

        $this->countryChoices  = FormFieldHelper::getCountryChoices();
        $this->regionChoices   = FormFieldHelper::getRegionChoices();
        $this->timezoneChoices = FormFieldHelper::getTimezonesChoices();
        $this->localeChoices   = FormFieldHelper::getLocaleChoices();

        $stages = $factory->getModel('stage')->getRepository()->getSimpleList();

        foreach ($stages as $stage) {
            $this->stageChoices[$stage['value']] = $stage['label'];
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html', 'customHtml' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('email.email', $options));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.email.form.internal.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $emojiTransformer = new EmojiToShortTransformer();
        $builder->add(
            $builder->create(
                'subject',
                'text',
                [
                    'label'      => 'mautic.email.subject',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            )->addModelTransformer($emojiTransformer)
        );

        $builder->add(
            'fromName',
            'text',
            [
                'label'      => 'mautic.email.from_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-user',
                    'tooltip'  => 'mautic.email.from_name.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'fromAddress',
            'text',
            [
                'label'      => 'mautic.email.from_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.from_email.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'replyToAddress',
            'text',
            [
                'label'      => 'mautic.email.reply_to_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.reply_to_email.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'bccAddress',
            'text',
            [
                'label'      => 'mautic.email.bcc',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                    'tooltip'  => 'mautic.email.bcc.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'utmTags',
            'utm_tags',
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
            'template',
            'theme_list',
            [
                'feature' => 'email',
                'attr'    => [
                    'class'   => 'form-control not-chosen hidden',
                    'tooltip' => 'mautic.email.form.template.help',
                ],
                'data' => $options['data']->getTemplate() ? $options['data']->getTemplate() : 'blank',
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add(
            'publishUp',
            'datetime',
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
            'datetime',
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                    'tooltip'     => 'mautic.email.form.publishdown.help',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add(
            'plainText',
            'textarea',
            [
                'label'      => 'mautic.email.form.plaintext',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'tooltip'              => 'mautic.email.form.plaintext.help',
                    'class'                => 'form-control',
                    'rows'                 => '15',
                    'data-token-callback'  => 'email:getBuilderTokens',
                    'data-token-activator' => '{',
                    'data-token-visual'    => 'false',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            $builder->create(
                'customHtml',
                'textarea',
                [
                    'label'      => 'mautic.email.form.body',
                    'label_attr' => ['class' => 'control-label'],
                    'required'   => false,
                    'attr'       => [
                        'class'                => 'form-control editor-builder-tokens builder-html editor-email',
                        'data-token-callback'  => 'email:getBuilderTokens',
                        'data-token-activator' => '{',
                    ],
                ]
            )->addModelTransformer($emojiTransformer)
        );

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticFormBundle:Form', 'id');
        $builder->add(
            $builder->create(
                'unsubscribeForm',
                'form_list',
                [
                    'label'      => 'mautic.email.form.unsubscribeform',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'            => 'form-control',
                        'tootlip'          => 'mautic.email.form.unsubscribeform.tooltip',
                        'data-placeholder' => $this->translator->trans('mautic.core.form.chooseone'),
                    ],
                    'required'    => false,
                    'multiple'    => false,
                    'empty_value' => '',
                ]
            )
                ->addModelTransformer($transformer)
        );

        $transformer = new IdToEntityModelTransformer($this->em, 'MauticEmailBundle:Email');
        $builder->add(
            $builder->create(
                'variantParent',
                'hidden'
            )->addModelTransformer($transformer)
        );

        $builder->add(
            $builder->create(
                'translationParent',
                'hidden'
            )->addModelTransformer($transformer)
        );

        $variantParent     = $options['data']->getVariantParent();
        $translationParent = $options['data']->getTranslationParent();
        $builder->add(
            'segmentTranslationParent',
            'email_list',
            [
                'label'      => 'mautic.core.form.translation_parent',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.form.translation_parent.help',
                ],
                'required'       => false,
                'multiple'       => false,
                'email_type'     => 'list',
                'empty_value'    => 'mautic.core.form.translation_parent.empty',
                'top_level'      => 'translation',
                'variant_parent' => ($variantParent) ? $variantParent->getId() : null,
                'ignore_ids'     => [(int) $options['data']->getId()],
                'mapped'         => false,
                'data'           => ($translationParent) ? $translationParent->getId() : null,
            ]
        );

        $builder->add(
            'templateTranslationParent',
            'email_list',
            [
                'label'      => 'mautic.core.form.translation_parent',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.form.translation_parent.help',
                ],
                'required'       => false,
                'multiple'       => false,
                'empty_value'    => 'mautic.core.form.translation_parent.empty',
                'top_level'      => 'translation',
                'variant_parent' => ($variantParent) ? $variantParent->getId() : null,
                'email_type'     => 'template',
                'ignore_ids'     => [(int) $options['data']->getId()],
                'mapped'         => false,
                'data'           => ($translationParent) ? $translationParent->getId() : null,
            ]
        );

        $url                     = $this->request->getSchemeAndHttpHost().$this->request->getBasePath();
        $variantSettingsModifier = function (FormEvent $event, $isVariant) use ($url) {
            if ($isVariant) {
                $event->getForm()->add(
                    'variantSettings',
                    'emailvariant',
                    [
                        'label' => false,
                    ]
                );
            }
        };

        // Building the form
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($variantSettingsModifier) {
                $variantSettingsModifier(
                    $event,
                    $event->getData()->getVariantParent()
                );
            }
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($variantSettingsModifier) {
                $data = $event->getData();
                $variantSettingsModifier(
                    $event,
                    !empty($data['variantParent'])
                );

                if (isset($data['emailType']) && $data['emailType'] == 'list') {
                    $data['translationParent'] = isset($data['segmentTranslationParent']) ? $data['segmentTranslationParent'] : null;
                } else {
                    $data['translationParent'] = isset($data['templateTranslationParent']) ? $data['templateTranslationParent'] : null;
                }

                $event->setData($data);
            }
        );

        //add category
        $builder->add(
            'category',
            'category',
            [
                'bundle' => 'email',
            ]
        );

        //add lead lists
        $transformer = new IdToEntityModelTransformer($this->em, 'MauticLeadBundle:LeadList', 'id', true);
        $builder->add(
            $builder->create(
                'lists',
                'leadlist_choices',
                [
                    'label'      => 'mautic.email.form.list',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'data-show-on' => '{"emailform_segmentTranslationParent":[""]}',
                    ],
                    'multiple' => true,
                    'expanded' => false,
                    'required' => true,
                ]
            )
                ->addModelTransformer($transformer)
        );

        $builder->add(
            'language',
            'locale',
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        //add lead lists
        $transformer = new IdToEntityModelTransformer(
            $this->em,
            'MauticAssetBundle:Asset',
            'id',
            true
        );
        $builder->add(
            $builder->create(
                'assetAttachments',
                'asset_list',
                [
                    'label'      => 'mautic.email.attachments',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.getTotalAttachmentSize();',
                    ],
                    'multiple' => true,
                    'expanded' => false,
                ]
            )
                ->addModelTransformer($transformer)
        );

        $builder->add('sessionId', 'hidden');
        $builder->add('emailType', 'hidden');

        $customButtons = [
            [
                'name'  => 'builder',
                'label' => 'mautic.core.builder',
                'attr'  => [
                    'class'   => 'btn btn-default btn-dnd btn-nospin text-primary btn-builder',
                    'icon'    => 'fa fa-cube',
                    'onclick' => "Mautic.launchBuilder('emailform', 'email');",
                ],
            ],
        ];

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'pre_extra_buttons' => $customButtons,
            ]
        );

        if (!empty($options['update_select'])) {
            $builder->add(
                'updateSelect',
                'hidden',
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        }

        $this->addDynamicContentField($builder);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\EmailBundle\Entity\Email',
            ]
        );

        $resolver->setDefined(['update_select']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['countries'] = $this->countryChoices;
        $view->vars['regions']   = $this->regionChoices;
        $view->vars['timezones'] = $this->timezoneChoices;
        $view->vars['stages']    = $this->stageChoices;
        $view->vars['locales']   = $this->localeChoices;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'emailform';
    }
}
