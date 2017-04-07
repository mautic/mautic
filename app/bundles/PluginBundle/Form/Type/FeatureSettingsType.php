<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FeatureSettingsType.
 */
class FeatureSettingsType extends AbstractType
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * FeatureSettingsType constructor.
     *
     * @param Session              $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param TranslatorInterface  $translator
     */
    public function __construct(
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
        $this->logger               = $logger;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObject = $options['integration_object'];

        //add custom feature settings
        $integrationObject->appendToForm($builder, $options['data'], 'features');
        $leadFields    = $options['lead_fields'];
        $companyFields = $options['company_fields'];
        $formSettings  = $options['integration_object']->getFormDisplaySettings();

        $formModifier = function (FormInterface $form, $data, $method = 'get') use ($integrationObject, $leadFields, $companyFields, $formSettings) {
            $integrationName = $integrationObject->getName();
            $session         = $this->session;
            $limit           = $session->get(
                'mautic.plugin.'.$integrationName.'.lead.limit',
                $this->coreParametersHelper->getParameter('default_pagelimit')
            );
            $page        = $session->get('mautic.plugin.'.$integrationName.'.lead.page', 1);
            $companyPage = $session->get('mautic.plugin.'.$integrationName.'.company.lead.page', 1);

            $settings = [
                'silence_exceptions' => false,
                'feature_settings'   => $data,
                'ignore_field_cache' => ($page == 1 && 'POST' !== $_SERVER['REQUEST_METHOD']) ? true : false,
            ];
            $totalFields = 0;
            try {
                if (empty($fields)) {
                    $fields = $integrationObject->getFormLeadFields($settings);
                    $fields = (isset($fields[0])) ? $fields[0] : $fields;
                    unset($fields['company']);
                }
                $totalFields = count($fields);

                if (isset($settings['feature_settings']['objects']) and in_array('company', $settings['feature_settings']['objects'])) {
                    if (empty($integrationCompanyFields)) {
                        $integrationCompanyFields = $integrationObject->getFormCompanyFields($settings);
                    }
                    $totalCompanyFields = count($integrationCompanyFields);
                    if (isset($integrationCompanyFields['company'])) {
                        $integrationCompanyFields = $integrationCompanyFields['company'];
                    }
                }

                if (!is_array($fields)) {
                    $fields = [];
                }
                $error = '';
            } catch (\Exception $e) {
                $fields = [];
                $error  = $e->getMessage();
                $this->logger->error($e);
            }
            list($specialInstructions, $alertType) = $integrationObject->getFormNotes('leadfield_match');

            $enableDataPriority = !empty($formSettings['enable_data_priority']);

            $form->add(
                'leadFields',
                'integration_fields',
                [
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'lead_fields'          => $leadFields,
                    'data'                 => isset($data['leadFields']) && !empty($data['leadFields']) ? $data['leadFields'] : [],
                    'update_mautic'        => isset($data['update_mautic']) && !empty($data['update_mautic']) ? $data['update_mautic'] : [],
                    'integration_fields'   => $fields,
                    'special_instructions' => $specialInstructions,
                    'alert_type'           => $alertType,
                    'enable_data_priority' => $enableDataPriority,
                    'integration'          => $integrationObject->getName(),
                    'totalFields'          => $totalFields,
                    'limit'                => $limit,
                    'start'                => (1 === $page) ? 0 : ($page - 1) * $limit,
                    'page'                 => $page,
                    'fixedPageNum'         => round($totalFields / $limit),
                    'mapped'               => false,
                    'error_bubbling'       => false,
                ]
            );
            if (!empty($integrationCompanyFields)) {
                $form->add(
                    'companyFields',
                    'integration_company_fields',
                    [
                        'label'                 => 'mautic.integration.comapanyfield_matches',
                        'required'              => false,
                        'company_fields'        => $companyFields,
                        'data'                  => isset($data['companyFields']) && !empty($data['companyFields']) ? $data['companyFields'] : [],
                        'update_mautic_company' => isset($data['update_mautic_company']) && !empty($data['update_mautic_company'])
                            ? $data['update_mautic_company'] : [],
                        'integration_company_fields' => $integrationCompanyFields,
                        'special_instructions'       => $specialInstructions,
                        'alert_type'                 => $alertType,
                        'enable_data_priority'       => $enableDataPriority,
                        'integration'                => $integrationObject->getName(),
                        'totalFields'                => $totalCompanyFields,
                        'limit'                      => $limit,
                        'start'                      => (1 === $companyPage) ? 0 : ($companyPage - 1) * $limit,
                        'page'                       => $companyPage,
                        'fixedPageNum'               => (round($totalCompanyFields / $limit)),
                        'mapped'                     => false,
                        'error_bubbling'             => false,
                    ]
                );
            }
            if ($method == 'get' && $error) {
                $form->addError(new FormError($error));
            }
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data, 'post');
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['integration', 'integration_object', 'lead_fields', 'company_fields']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_featuresettings';
    }
}
