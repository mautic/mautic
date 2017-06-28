<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\IpLookupFactory;
use Mautic\CoreBundle\Form\DataTransformer\ArrayLinebreakTransformer;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\IpLookup\AbstractLookup;
use Mautic\CoreBundle\IpLookup\IpLookupFormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
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
    private $ipLookupChoices;

    /**
     * @var array
     */
    private $supportedLanguages;

    /**
     * @var
     */
    private $ipLookupFactory;

    /**
     * @var AbstractLookup
     */
    private $ipLookup;

    /**
     * ConfigType constructor.
     *
     * @param TranslatorInterface $translator
     * @param LanguageHelper      $langHelper
     * @param IpLookupFactory     $ipLookupFactory
     * @param array               $supportedLanguages
     * @param array               $ipLookupServices
     * @param AbstractLookup      $ipLookup
     */
    public function __construct(
        TranslatorInterface $translator,
        LanguageHelper $langHelper,
        IpLookupFactory $ipLookupFactory,
        array $supportedLanguages,
        array $ipLookupServices,
        AbstractLookup $ipLookup = null
    ) {
        $this->translator         = $translator;
        $this->langHelper         = $langHelper;
        $this->ipLookupFactory    = $ipLookupFactory;
        $this->ipLookup           = $ipLookup;
        $this->supportedLanguages = $supportedLanguages;

        $choices = [];
        foreach ($ipLookupServices as $name => $service) {
            $choices[$name] = $service['display_name'];
        }

        natcasesort($choices);

        $this->ipLookupChoices = $choices;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'site_url',
            'text',
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
            'page_list',
            [
                'label'      => 'mautic.core.config.form.webroot',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'            => 'form-control',
                    'tooltip'          => 'mautic.core.config.form.webroot.tooltip',
                    'data-placeholder' => $this->translator->trans('mautic.core.config.form.webroot.dashboard'),
                ],
                'multiple'    => false,
                'empty_value' => '',
                'required'    => false,
            ]
        );

        $builder->add(
            'cache_path',
            'text',
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
            'text',
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
            'text',
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
            'theme',
            'theme_list',
            [
                'label' => 'mautic.core.config.form.theme',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.template.help',
                ],
            ]
        );

        // Get the list of available languages
        $languages   = $this->langHelper->fetchLanguages(false, false);
        $langChoices = [];

        foreach ($languages as $code => $langData) {
            $langChoices[$code] = $langData['name'];
        }

        $langChoices = array_merge($langChoices, $this->supportedLanguages);

        // Alpha sort the languages by name
        asort($langChoices);

        $builder->add(
            'locale',
            'choice',
            [
                'choices'  => $langChoices,
                'label'    => 'mautic.core.config.form.locale',
                'required' => false,
                'attr'     => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.locale.tooltip',
                ],
                'empty_value' => false,
            ]
        );

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create(
                'trusted_hosts',
                'text',
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
                'text',
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
                'textarea',
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
                'textarea',
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
            'choice',
            [
                'choices' => [
                    5   => 'mautic.core.pagination.5',
                    10  => 'mautic.core.pagination.10',
                    15  => 'mautic.core.pagination.15',
                    20  => 'mautic.core.pagination.20',
                    25  => 'mautic.core.pagination.25',
                    30  => 'mautic.core.pagination.30',
                    50  => 'mautic.core.pagination.50',
                    100 => 'mautic.core.pagination.100',
                ],
                'expanded'   => false,
                'multiple'   => false,
                'label'      => 'mautic.core.config.form.default.pagelimit',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.pagelimit.tooltip',
                ],
                'required'    => false,
                'empty_value' => false,
            ]
        );

        $builder->add(
            'default_timezone',
            'timezone',
            [
                'label'      => 'mautic.core.config.form.default.timezone',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.timezone.tooltip',
                ],
                'multiple'    => false,
                'empty_value' => 'mautic.user.user.form.defaulttimezone',
                'required'    => false,
            ]
        );

        $builder->add(
            'cached_data_timeout',
            'text',
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
            'text',
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
            'text',
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
            'text',
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
            'text',
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
            'ip_lookup_service',
            'choice',
            [
                'choices'    => $this->ipLookupChoices,
                'label'      => 'mautic.core.config.form.ip.lookup.service',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'required' => false,
                'attr'     => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.core.config.form.ip.lookup.service.tooltip',
                    'onchange' => 'Mautic.getIpLookupFormConfig()',
                ],
            ]
        );

        $builder->add(
            'ip_lookup_auth',
            'text',
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
            'transifex_username',
            'text',
            [
                'label'      => 'mautic.core.config.form.transifex.username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.core.config.form.transifex.username.tooltip',
                    'autocomplete' => 'off',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'transifex_password',
            'password',
            [
                'label'      => 'mautic.core.config.form.transifex.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'tooltip'      => 'mautic.core.config.form.transifex.password.tooltip',
                    'autocomplete' => 'off',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'update_stability',
            'choice',
            [
                'choices' => [
                    'alpha'  => 'mautic.core.config.update_stability.alpha',
                    'beta'   => 'mautic.core.config.update_stability.beta',
                    'rc'     => 'mautic.core.config.update_stability.rc',
                    'stable' => 'mautic.core.config.update_stability.stable',
                ],
                'label'    => 'mautic.core.config.form.update.stability',
                'required' => false,
                'attr'     => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.update.stability.tooltip',
                ],
                'empty_value' => false,
            ]
        );

        $builder->add(
            'link_shortener_url',
            'text',
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
            'number',
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
            'cors_restrict_domains',
            'yesno_button_group',
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
                'textarea',
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
    public function getName()
    {
        return 'coreconfig';
    }
}
