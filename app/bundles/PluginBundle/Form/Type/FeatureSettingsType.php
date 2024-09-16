<?php

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
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Session $session,
        CoreParametersHelper $coreParametersHelper,
        LoggerInterface $logger
    ) {
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->logger               = $logger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObject = $options['integration_object'];

        //add custom feature settings
        $integrationObject->appendToForm($builder, $options['data'], 'features');
        $leadFields    = $options['lead_fields'];
        $companyFields = $options['company_fields'];

        $formModifier = function (FormInterface $form, $data, $method = 'get') use ($integrationObject, $leadFields, $companyFields) {
            $integrationName = $integrationObject->getName();
            $session         = $this->session;
            $limit           = $session->get(
                'mautic.plugin.'.$integrationName.'.lead.limit',
                $this->coreParametersHelper->get('default_pagelimit')
            );
            $page        = $session->get('mautic.plugin.'.$integrationName.'.lead.page', 1);
            $companyPage = $session->get('mautic.plugin.'.$integrationName.'.company.page', 1);

            $settings = [
                'silence_exceptions' => false,
                'feature_settings'   => $data,
                'ignore_field_cache' => (1 == $page && 'POST' !== $_SERVER['REQUEST_METHOD']) ? true : false,
            ];

            try {
                if (empty($fields)) {
                    $fields = $integrationObject->getFormLeadFields($settings);
                    $fields = (isset($fields[0])) ? $fields[0] : $fields;
                }

                if (isset($settings['feature_settings']['objects']) and in_array('company', $settings['feature_settings']['objects'])) {
                    if (empty($integrationCompanyFields)) {
                        $integrationCompanyFields = $integrationObject->getFormCompanyFields($settings);
                    }
                    if (isset($integrationCompanyFields['company'])) {
                        $integrationCompanyFields = $integrationCompanyFields['company'];
                    }
                }

                if (!is_array($fields)) {
                    $fields = [];
                }
                $error = '';
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $this->logger->error($e);

                // Prevent pagination from confusing things by using the cache
                $page   = 1;
                $fields = $integrationCompanyFields = [];
            }

            $enableDataPriority = $integrationObject->getDataPriority();

            $form->add(
                'leadFields',
                FieldsType::class,
                [
                    'label'                => 'mautic.integration.leadfield_matches',
                    'required'             => true,
                    'mautic_fields'        => $leadFields,
                    'data'                 => $data,
                    'integration_fields'   => $fields,
                    'enable_data_priority' => $enableDataPriority,
                    'integration'          => $integrationObject->getName(),
                    'integration_object'   => $integrationObject,
                    'limit'                => $limit,
                    'page'                 => $page,
                    'mapped'               => false,
                    'error_bubbling'       => false,
                ]
            );

            if (!empty($integrationCompanyFields)) {
                $form->add(
                    'companyFields',
                    CompanyFieldsType::class,
                    [
                        'label'                => 'mautic.integration.companyfield_matches',
                        'required'             => true,
                        'mautic_fields'        => $companyFields,
                        'data'                 => $data,
                        'integration_fields'   => $integrationCompanyFields,
                        'enable_data_priority' => $enableDataPriority,
                        'integration'          => $integrationObject->getName(),
                        'integration_object'   => $integrationObject,
                        'limit'                => $limit,
                        'page'                 => $companyPage,
                        'mapped'               => false,
                        'error_bubbling'       => false,
                    ]
                );
            }
            if ('get' == $method && $error) {
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
    public function getBlockPrefix()
    {
        return 'integration_featuresettings';
    }
}
