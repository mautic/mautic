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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, Session $session, CoreParametersHelper $coreParametersHelper, TranslatorInterface $translator)
    {
        $this->factory              = $factory;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integration_object = $options['integration_object'];

        //add custom feature settings
        $integration_object->appendToForm($builder, $options['data'], 'features');

        $leadFields    = $options['lead_fields'];
        $companyFields = $options['company_fields'];
        $formSettings  = $options['integration_object']->getFormDisplaySettings();

        $formModifier = function (FormInterface $form, $data, $method = 'get') use ($integration_object, $leadFields, $companyFields, $formSettings) {
            $session = $this->session;
            $limit   = $session->get('mautic.lead.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
            $page    = $session->get('mautic.plugin.lead.page', 1);
            $start   = $session->get('mautic.plugin.lead.start', 1);

            $companyPage  = $session->get('mautic.plugin.company.start', 1);
            $companyStart = $session->get('mautic.plugin.company.lead.page', 1);

            $settings = [
                'silence_exceptions' => false,
                'feature_settings'   => $data,
                'ignore_field_cache' => ('GET' === $_SERVER['REQUEST_METHOD']),
            ];
            try {
                $fields      = $integration_object->getFormLeadFields($settings);
                $totalFields = count($fields);
                $fields      = array_slice($fields, $start, $limit);
                $fields      = (isset($fields[0])) ? $fields[0] : $fields;
                unset($fields['company']);
                if (isset($settings['feature_settings']['objects']) and in_array('company', $settings['feature_settings']['objects'])) {
                    $integrationCompanyFields = $integration_object->getFormCompanyFields($settings);
                    $totalCompanyFields       = count($integrationCompanyFields);
                    $integrationCompanyFields = array_slice($integrationCompanyFields, $companyStart, $limit);
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
            }
            list($specialInstructions, $alertType) = $integration_object->getFormNotes('leadfield_match');
            /**
             * Auto Match Integration Fields with Mautic Fields.
             */
            $flattenLeadFields = [];
            foreach (array_values($leadFields) as $fieldsWithoutGroups) {
                $flattenLeadFields = array_merge($flattenLeadFields, $fieldsWithoutGroups);
            }
            $integrationFields  = array_keys($fields);
            $flattenLeadFields  = array_keys($flattenLeadFields);
            $fieldsIntersection = array_uintersect($integrationFields, $flattenLeadFields, 'strcasecmp');
            $enableDataPriority = false;
            if (isset($formSettings['enable_data_priority'])) {
                $enableDataPriority = $formSettings['enable_data_priority'];
            }

            $autoMatchedFields = [];
            foreach ($fieldsIntersection as $field) {
                $autoMatchedFields[$field] = strtolower($field);
            }
            $leadFields['-1'] = $this->translator->trans('mautic.plugin.integration.option.not.matched');
            $form->add(
                'leadFields',
                'integration_fields',
                [
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'lead_fields'          => $leadFields,
                    'data'                 => isset($data['leadFields']) && !empty($data['leadFields']) ? $data['leadFields'] : $autoMatchedFields,
                    'update_mautic'        => isset($data['update_mautic']) && !empty($data['update_mautic']) ? $data['update_mautic'] : [],
                    'integration_fields'   => $fields,
                    'special_instructions' => $specialInstructions,
                    'alert_type'           => $alertType,
                    'enable_data_priority' => $enableDataPriority,
                    'integration'          => $integration_object->getName(),
                    'totalFields'          => $totalFields,
                    'page'                 => $page,
                    'fixedPageNum'         => round($totalFields / $limit),
                ]
            );
            if (!empty($integrationCompanyFields)) {
                $companyFields['-1'] = $this->translator->trans('mautic.plugin.integration.option.not.matched');
                $form->add(
                    'companyFields',
                    'integration_company_fields',
                    [
                        'label'                      => 'mautic.integration.comapanyfield_matches',
                        'required'                   => false,
                        'company_fields'             => $companyFields,
                        'data'                       => isset($data['companyFields']) && !empty($data['companyFields']) ? $data['companyFields'] : [],
                        'update_mautic_company'      => isset($data['update_mautic_company']) && !empty($data['update_mautic_company']) ? $data['update_mautic_company'] : [],
                        'integration_company_fields' => $integrationCompanyFields,
                        'special_instructions'       => $specialInstructions,
                        'alert_type'                 => $alertType,
                        'enable_data_priority'       => $enableDataPriority,
                        'integration'                => $integration_object->getName(),
                        'totalFields'                => $totalCompanyFields,
                        'page'                       => $companyPage,
                        'fixedPageNum'               => round($totalCompanyFields / $limit),
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
