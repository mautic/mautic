<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
 * Class ConfigType
 *
 * @package Mautic\CoreBundle\Form\Type
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
     * @var $ipLookupFactory
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

        $choices = array();
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
            array(
                'label'       => 'mautic.core.config.form.site.url',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.site.url.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'webroot',
            'page_list',
            array(
                'label'       => 'mautic.core.config.form.webroot',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'            => 'form-control',
                    'tooltip'          => 'mautic.core.config.form.webroot.tooltip',
                    'data-placeholder' => $this->translator->trans('mautic.core.config.form.webroot.dashboard')
                ),
                'multiple'    => false,
                'empty_value' => '',
                'required'    => false
            )
        );

        $builder->add(
            'cache_path',
            'text',
            array(
                'label'       => 'mautic.core.config.form.cache.path',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.cache.path.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'log_path',
            'text',
            array(
                'label'       => 'mautic.core.config.form.log.path',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.log.path.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'image_path',
            'text',
            array(
                'label'       => 'mautic.core.config.form.image.path',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.image.path.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'theme',
            'theme_list',
            array(
                'label' => 'mautic.core.config.form.theme',
                'attr'  => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.template.help'
                )
            )
        );

        // Get the list of available languages
        $languages   = $this->langHelper->fetchLanguages(false, false);
        $langChoices = array();

        foreach ($languages as $code => $langData) {
            $langChoices[$code] = $langData['name'];
        }

        $langChoices = array_merge($langChoices, $this->supportedLanguages);

        // Alpha sort the languages by name
        asort($langChoices);

        $builder->add(
            'locale',
            'choice',
            array(
                'choices'     => $langChoices,
                'label'       => 'mautic.core.config.form.locale',
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.locale.tooltip'
                ),
                'empty_value' => false
            )
        );

        $arrayStringTransformer = new ArrayStringTransformer();
        $builder->add(
            $builder->create(
                'trusted_hosts',
                'text',
                array(
                    'label'      => 'mautic.core.config.form.trusted.hosts',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.trusted.hosts.tooltip'
                    ),
                    'required'   => false
                )
            )->addViewTransformer($arrayStringTransformer)
        );

        $builder->add(
            $builder->create(
                'trusted_proxies',
                'text',
                array(
                    'label'      => 'mautic.core.config.form.trusted.proxies',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.trusted.proxies.tooltip'
                    ),
                    'required'   => false
                )
            )->addViewTransformer($arrayStringTransformer)
        );

        $arrayLinebreakTransformer = new ArrayLinebreakTransformer();
        $builder->add(
            $builder->create(
                'do_not_track_ips',
                'textarea',
                array(
                    'label'      => 'mautic.core.config.form.do_not_track_ips',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.config.form.do_not_track_ips.tooltip'
                    ),
                    'required'   => false
                )
            )->addViewTransformer($arrayLinebreakTransformer)
        );

        $builder->add(
            'rememberme_key',
            'text',
            array(
                'label'       => 'mautic.core.config.form.rememberme.key',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.rememberme.key.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'rememberme_lifetime',
            'text',
            array(
                'label'       => 'mautic.core.config.form.rememberme.lifetime',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.rememberme.lifetime.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'rememberme_path',
            'text',
            array(
                'label'       => 'mautic.core.config.form.rememberme.path',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.rememberme.path.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'rememberme_domain',
            'text',
            array(
                'label'      => 'mautic.core.config.form.rememberme.domain',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.rememberme.domain.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'default_pagelimit',
            'choice',
            array(
                'choices'     => array(
                    5   => 'mautic.core.pagination.5',
                    10  => 'mautic.core.pagination.10',
                    15  => 'mautic.core.pagination.15',
                    20  => 'mautic.core.pagination.20',
                    25  => 'mautic.core.pagination.25',
                    30  => 'mautic.core.pagination.30',
                    50  => 'mautic.core.pagination.50',
                    100 => 'mautic.core.pagination.100'
                ),
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.core.config.form.default.pagelimit',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.pagelimit.tooltip'
                ),
                'required'    => false,
                'empty_value' => false
            )
        );

        $builder->add(
            'default_timezone',
            'timezone',
            array(
                'label'       => 'mautic.core.config.form.default.timezone',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.default.timezone.tooltip'
                ),
                'multiple'    => false,
                'empty_value' => 'mautic.user.user.form.defaulttimezone',
                'required'    => false
            )
        );

        $builder->add(
            'cached_data_timeout',
            'text',
            array(
                'label'       => 'mautic.core.config.form.cached.data.timeout',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'          => 'form-control',
                    'tooltip'        => 'mautic.core.config.form.cached.data.timeout.tooltip',
                    'postaddon'      => '',
                    'postaddon_text' => $this->translator->trans('mautic.core.time.minutes')
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'date_format_full',
            'text',
            array(
                'label'       => 'mautic.core.config.form.date.format.full',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.full.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'date_format_short',
            'text',
            array(
                'label'       => 'mautic.core.config.form.date.format.short',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.short.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'date_format_dateonly',
            'text',
            array(
                'label'       => 'mautic.core.config.form.date.format.dateonly',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.dateonly.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'date_format_timeonly',
            'text',
            array(
                'label'       => 'mautic.core.config.form.date.format.timeonly',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.date.format.timeonly.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'ip_lookup_service',
            'choice',
            array(
                'choices'    => $this->ipLookupChoices,
                'label'      => 'mautic.core.config.form.ip.lookup.service',
                'label_attr' => array(
                    'class' => 'control-label'
                ),
                'required'   => false,
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.ip.lookup.service.tooltip',
                    'onchange' => 'Mautic.getIpLookupFormConfig()'
                )
            )
        );

        $builder->add(
            'ip_lookup_auth',
            'text',
            array(
                'label'      => 'mautic.core.config.form.ip.lookup.auth',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.ip.lookup.auth.tooltip'
                ),
                'required'   => false
            )
        );

        $ipLookupFactory = $this->ipLookupFactory;
        $formModifier = function (FormEvent $event) use ($ipLookupFactory) {
            $data    = $event->getData();
            $form    = $event->getForm();

            $ipServiceName = (isset($data['ip_lookup_service'])) ? $data['ip_lookup_service'] : null;
            if ($ipServiceName && $lookupService = $ipLookupFactory->getService($ipServiceName)) {
                if ($lookupService instanceof IpLookupFormInterface && $formType = $lookupService->getConfigFormService()) {
                    $form->add(
                        'ip_lookup_config',
                        $formType,
                        array(
                            'label'             => false,
                            'ip_lookup_service' => $lookupService
                        )
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
            array(
                'label'      => 'mautic.core.config.form.transifex.username',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.core.config.form.transifex.username.tooltip',
                    'autocomplete' => 'off'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'transifex_password',
            'password',
            array(
                'label'      => 'mautic.core.config.form.transifex.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'tooltip'      => 'mautic.core.config.form.transifex.password.tooltip',
                    'autocomplete' => 'off'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'update_stability',
            'choice',
            array(
                'choices'     => array(
                    'alpha'  => 'mautic.core.config.update_stability.alpha',
                    'beta'   => 'mautic.core.config.update_stability.beta',
                    'rc'     => 'mautic.core.config.update_stability.rc',
                    'stable' => 'mautic.core.config.update_stability.stable'
                ),
                'label'       => 'mautic.core.config.form.update.stability',
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.update.stability.tooltip'
                ),
                'empty_value' => false
            )
        );

        $builder->add(
            'cookie_path',
            'text',
            array(
                'label'       => 'mautic.core.config.form.cookie.path',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.cookie.path.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'cookie_domain',
            'text',
            array(
                'label'      => 'mautic.core.config.form.cookie.domain',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.config.form.cookie.domain.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'cookie_secure',
            'yesno_button_group',
            array(
                'label'       => 'mautic.core.config.form.cookie.secure',
                'empty_value' => 'mautic.core.form.default',
                'data'        => (array_key_exists('cookie_secure', $options['data']) && !empty($options['data']['cookie_secure'])) ? true : false,
                'attr'        => array(
                    'tooltip' => 'mautic.core.config.form.cookie.secure.tooltip'
                )
            )
        );

        $builder->add(
            'cookie_httponly',
            'yesno_button_group',
            array(
                'label' => 'mautic.core.config.form.cookie.httponly',
                'data'  => (array_key_exists('cookie_httponly', $options['data']) && !empty($options['data']['cookie_httponly'])) ? true : false,
                'attr'  => array(
                    'tooltip' => 'mautic.core.config.form.cookie.httponly.tooltip'
                )
            )
        );

        $builder->add(
            'link_shortener_url',
            'text',
            array(
                'label'      => 'mautic.core.config.form.link.shortener',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.core.config.form.link.shortener.tooltip'
                ),
                'required'   => false
            )
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
