<?php

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\IpLookupFactory;
use Mautic\CoreBundle\Form\DataTransformer\ArrayLinebreakTransformer;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\CoreBundle\IpLookup\IpLookupFormInterface;
use Mautic\PageBundle\Form\Type\PageListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LanguageHelper
     */
    private $langHelper;

    /**
     * @var array
     */
    private $supportedLanguages;

    /**
     * @var IpLookupFactory
     */
    private $ipLookupFactory;

    /**
     * @var AbstractLookup
     */
    private $ipLookup;

    /**
     * @var array
     */
    private $ipLookupServices;

    public function __construct(
        TranslatorInterface $translator,
        LanguageHelper $langHelper,
        IpLookupFactory $ipLookupFactory,
        array $ipLookupServices,
        AbstractLookup $ipLookup = null
    ) {
        $this->translator          = $translator;
        $this->langHelper          = $langHelper;
        $this->ipLookupFactory     = $ipLookupFactory;
        $this->ipLookup            = $ipLookup;
        $this->supportedLanguages  = $langHelper->getSupportedLanguages();
        $this->ipLookupServices    = $ipLookupServices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('last_shown_tab', HiddenType::class);

        $builder->add(
            'site_url',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.site.url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.site.url.tooltip',
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
            'webroot',
            PageListType::class,
            [
                'label'      => 'mautic.core.config.form.webroot',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'            => 'form-control',
                    'tooltip'          => 'mautic.core.config.form.webroot.tooltip',
                    'data-placeholder' => $this->translator->trans('mautic.core.config.form.webroot.dashboard'),
                ],
                'multiple'    => false,
                'placeholder' => '',
                'required'    => false,
            ]
        );

        $builder->add(
            '404_page',
            PageListType::class,
            [
                'label'         => 'mautic.core.config.form.404_page',
                'label_attr'    => ['class' => 'control-label'],
                'attr'          => [
                    'class'            => 'form-control',
                    'tooltip'          => 'mautic.core.config.form.404_page.tooltip',
                ],
                'multiple'       => false,
                'placeholder'    => '',
                'published_only' => true,
            ]
        );

        $builder->add(
            'cache_path',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.cache.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.cache.path.tooltip',
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
            'log_path',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.log.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.log.path.tooltip',
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
            'image_path',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.image.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.image.path.tooltip',
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
            'composer_updates',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.core.config.form.update.composer',
                'data'  => (array_key_exists('composer_updates', $options['data']) && !empty($options['data']['composer_updates'])),
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.update.composer.tooltip',
                ],
            ]
        );

        $builder->add(
            'locale',
            ChoiceType::class,
            [
                'choices'           => $this->getLanguageChoices(),
                'label'             => 'mautic.core.config.form.locale',
                'required'          => false,
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.locale.tooltip',
                ],
                'placeholder'       => false,
            ]
        );

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create(
                'trusted_hosts',
                TextType::class,
                [
                    'label'      => 'mautic.core.config.form.trusted.hosts',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.trusted.hosts.tooltip',
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayStringTransformer)
        );

        $builder->add(
            $builder->create(
                'trusted_proxies',
                TextType::class,
                [
                    'label'      => 'mautic.core.config.form.trusted.proxies',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.trusted.proxies.tooltip',
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayStringTransformer)
        );

        $arrayLinebreakTransformer = new ArrayLinebreakTransformer();
        $builder->add(
            $builder->create(
                'do_not_track_ips',
                TextareaType::class,
                [
                    'label'      => 'mautic.core.config.form.do_not_track_ips',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.do_not_track_ips.tooltip',
                        'rows'    => 8,
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayLinebreakTransformer)
        );

        $builder->add(
            $builder->create(
                'do_not_track_bots',
                TextareaType::class,
                [
                    'label'      => 'mautic.core.config.form.do_not_track_bots',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.do_not_track_bots.tooltip',
                        'rows'    => 8,
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayLinebreakTransformer)
        );

        $builder->add(
            'default_pagelimit',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.core.pagination.5'   => 5,
                    'mautic.core.pagination.10'  => 10,
                    'mautic.core.pagination.15'  => 15,
                    'mautic.core.pagination.20'  => 20,
                    'mautic.core.pagination.25'  => 25,
                    'mautic.core.pagination.30'  => 30,
                    'mautic.core.pagination.50'  => 50,
                    'mautic.core.pagination.100' => 100,
                ],
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.core.config.form.default.pagelimit',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.pagelimit.tooltip',
                ],
                'required'          => false,
                'placeholder'       => false,
            ]
        );

        $builder->add(
            'default_timezone',
            TimezoneType::class,
            [
                'label'      => 'mautic.core.config.form.default.timezone',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.timezone.tooltip',
                ],
                'multiple'    => false,
                'placeholder' => 'mautic.user.user.form.defaulttimezone',
                'required'    => false,
            ]
        );

        $builder->add(
            'cached_data_timeout',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.cached.data.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'          => 'form-control',
                    'tooltip'        => 'mautic.core.config.form.cached.data.timeout.tooltip',
                    'postaddon'      => '',
                    'postaddon_text' => $this->translator->trans('mautic.core.time.minutes'),
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
            'date_format_full',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.date.format.full',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.full.tooltip',
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
            'date_format_short',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.date.format.short',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.short.tooltip',
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
            'date_format_dateonly',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.date.format.dateonly',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.dateonly.tooltip',
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
            'date_format_timeonly',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.date.format.timeonly',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.timeonly.tooltip',
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
            'default_daterange_filter',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.core.daterange.0days'                                                                 => 'midnight',
                    'mautic.core.daterange.1days'                                                                 => '-24 hours',
                    $this->translator->trans('mautic.core.daterange.week', ['%count%' => 1])  => '-1 week',
                    $this->translator->trans('mautic.core.daterange.week', ['%count%' => 2])  => '-2 weeks',
                    $this->translator->trans('mautic.core.daterange.week', ['%count%' => 3])  => '-3 weeks',
                    $this->translator->trans('mautic.core.daterange.month', ['%count%' => 1]) => '-1 month',
                    $this->translator->trans('mautic.core.daterange.month', ['%count%' => 2]) => '-2 months',
                    $this->translator->trans('mautic.core.daterange.month', ['%count%' => 3]) => '-3 months',
                    $this->translator->trans('mautic.core.daterange.year', ['%count%' => 1])  => '-1 year',
                    $this->translator->trans('mautic.core.daterange.year', ['%count%' => 2])  => '-2 years',
                ],
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.core.config.form.default.daterange_default',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.daterange_default.tooltip',
                ],
                'required'          => false,
                'placeholder'       => false,
            ]
        );

        $builder->add(
            'ip_lookup_service',
            ChoiceType::class,
            [
                'choices'           => $this->getIpServicesChoices(),
                'label'             => 'mautic.core.config.form.ip.lookup.service',
                'label_attr'        => [
                    'class' => 'control-label',
                ],
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.core.config.form.ip.lookup.service.tooltip',
                    'onchange' => 'Mautic.getIpLookupFormConfig()',
                ],
            ]
        );

        $builder->add(
            'ip_lookup_auth',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.ip.lookup.auth',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.ip.lookup.auth.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'ip_lookup_create_organization',
            YesNoButtonGroupType::class,
            [
                'label'      => 'mautic.core.config.create.organization.from.ip.lookup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.create.organization.from.ip.lookup.tooltip',
                ],
                'data'     => isset($options['data']['ip_lookup_create_organization']) ? (bool) $options['data']['ip_lookup_create_organization'] : false,
                'required' => false,
            ]
        );

        $ipLookupFactory = $this->ipLookupFactory;
        $formModifier    = function (FormEvent $event) use ($ipLookupFactory) {
            $data = $event->getData();
            $form = $event->getForm();

            $ipServiceName = (isset($data['ip_lookup_service'])) ? $data['ip_lookup_service'] : null;
            if ($ipServiceName && $lookupService = $ipLookupFactory->getService($ipServiceName)) {
                if ($lookupService instanceof IpLookupFormInterface && $formType = $lookupService->getConfigFormService()) {
                    $form->add(
                        'ip_lookup_config',
                        $formType,
                        [
                            'label'             => false,
                            'ip_lookup_service' => $lookupService,
                        ]
                    );
                }
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event);
            }
        );

        $builder->add(
            'update_stability',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.core.config.update_stability.alpha'  => 'alpha',
                    'mautic.core.config.update_stability.beta'   => 'beta',
                    'mautic.core.config.update_stability.rc'     => 'rc',
                    'mautic.core.config.update_stability.stable' => 'stable',
                ],
                'label'             => 'mautic.core.config.form.update.stability',
                'required'          => false,
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.update.stability.tooltip',
                ],
                'placeholder'       => false,
            ]
        );

        $builder->add(
            'link_shortener_url',
            TextType::class,
            [
                'label'      => 'mautic.core.config.form.link.shortener',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.link.shortener.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'max_entity_lock_time',
            NumberType::class,
            [
                'label'      => 'mautic.core.config.form.link.max_entity_lock_time',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.link.max_entity_lock_time.tooltip',
                ],
                'required' => false,
            ]
            );

        $builder->add(
          'transliterate_page_title',
          YesNoButtonGroupType::class,
          [
            'label' => 'mautic.core.config.form.transliterate.page.title',
            'data'  => (array_key_exists('transliterate_page_title', $options['data']) && !empty($options['data']['transliterate_page_title'])),
            'attr'  => [
              'class'   => 'form-control',
              'tooltip' => 'mautic.core.config.form.transliterate.page.title.tooltip',
            ],
          ]
        );

        $builder->add(
            'cors_restrict_domains',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.core.config.cors.restrict.domains',
                'data'  => (array_key_exists('cors_restrict_domains', $options['data']) && !empty($options['data']['cors_restrict_domains'])),
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.cors.restrict.domains.tooltip',
                ],
            ]
        );

        $arrayLinebreakTransformer = new ArrayLinebreakTransformer();
        $builder->add(
            $builder->create(
                'cors_valid_domains',
                TextareaType::class,
                [
                    'label'      => 'mautic.core.config.cors.valid.domains',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.core.config.cors.valid.domains.tooltip',
                        'data-show-on' => '{"config_coreconfig_cors_restrict_domains_1":"checked"}',
                    ],
                ]
            )->addViewTransformer($arrayLinebreakTransformer)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['ipLookupAttribution'] = (null !== $this->ipLookup) ? $this->ipLookup->getAttribution() : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'coreconfig';
    }

    private function getLanguageChoices(): array
    {
        // Get the list of available languages
        $languages   = $this->langHelper->fetchLanguages(false, false);
        $choices     = [];

        foreach ($languages as $code => $langData) {
            $choices[$langData['name']] = $code;
        }

        $choices = array_merge($choices, array_flip($this->supportedLanguages));

        // Alpha sort the languages by name
        ksort($choices, SORT_FLAG_CASE | SORT_NATURAL);

        return $choices;
    }

    private function getIpServicesChoices(): array
    {
        $choices = [];
        foreach ($this->ipLookupServices as $name => $service) {
            $choices[$service['display_name']] = $name;
        }

        ksort($choices, SORT_FLAG_CASE | SORT_NATURAL);

        return $choices;
    }
}
