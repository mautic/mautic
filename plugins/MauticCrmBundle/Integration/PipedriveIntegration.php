<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveProduct;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveStage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PipedriveIntegration extends CrmAbstractIntegration
{
    const INTEGRATION_NAME         = 'Pipedrive';
    const PERSON_ENTITY_TYPE       = 'person';
    const LEAD_ENTITY_TYPE         = 'lead';
    const ORGANIZATION_ENTITY_TYPE = 'organization';
    const COMPANY_ENTITY_TYPE      = 'company';

    private $apiHelper;

    private $requiredFields = [
        'organization' => ['name'],
    ];

    /**
     * @return string
     */
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'token',
            'password',
        ];
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'url'      => 'mautic.pipedrive.api_url',
            'token'    => 'mautic.pipedrive.token',
            'user'     => 'mautic.pipedrive.webhook_user',
            'password' => 'mautic.pipedrive.webhook_password',
        ];
    }

    public function getApiUrl()
    {
        return $this->getKeys()['url'];
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getClientSecretKey()]);
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        $fields = $this->getAvailableLeadFields(self::ORGANIZATION_ENTITY_TYPE);

        return $fields;
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        $fields = $this->getAvailableLeadFields(self::PERSON_ENTITY_TYPE);

        // handle fields with are available in Pipedrive, but not listed
        return array_merge($fields, [
            'last_name' => [
                'label'    => 'Last Name',
                'required' => true,
            ],
            'first_name' => [
                'label'    => 'First Name',
                'required' => true,
            ],
        ]);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($object = null)
    {
        $integrationFields = [];

        /**
         * $object as Array comes from clicking "Apply" button on Plugins Configuration form.
         * I dont't know why its calling again pipedrive API to get fields which are already inside form...
         * Also i have no idea why is trying to pass some strange object...
         */
        if (!$this->isAuthorized() || !$object || is_array($object)) {
            return $integrationFields;
        }

        try {
            $leadFields = $this->getApiHelper()->getFields($object);

            if (!isset($leadFields)) {
                return $integrationFields;
            }

            foreach ($leadFields as $fieldInfo) {
                $integrationFields[$fieldInfo['key']] = [
                    'label'    => $fieldInfo['name'],
                    'required' => isset($this->requiredFields[$object]) && in_array($fieldInfo['key'], $this->requiredFields[$object]),
                ];
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $integrationFields;
    }

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        if (empty($this->apiHelper)) {
            $client          = $this->factory->get('mautic_integration.service.transport');
            $class           = '\\MauticPlugin\\MauticCrmBundle\\Api\\'.$this->getName().'Api'; //TODO replace with service
            $this->apiHelper = new $class($this, $client);
        }

        return $this->apiHelper;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'company' => 'mautic.pipedrive.object.organization',
                        'deal'    => 'mautic.pipedrive.object.deal',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.pipedrive.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        } elseif ($formArea == 'integration') {

            /**
             * formname can take several values like formaction or campaignevent
             * which makes it impossible to hardcode a value like below.
             *
             * Moreover, even if it could be done, there is an issue with the
             * first time the form is loaded as the expression for the pushDeal
             * is not evaluated at that moment. After a click on it, it works as
             * expected.
             */

            $formName = 'formaction_properties_config';
            //            $dontPushDeal = '{"'. $formName . '_push_deal_0": "checked"}'; // does that even work ?
            $pushDeal = '{"'. $formName . '_push_deal_1": "checked"}';
            $noProductChosen = '{"'. $formName . '_product": ""}';

            if ($this->isDealSupportEnabled()) {
                $builder->add(
                    'push_deal',
                    'yesno_button_group',
                    [
                        'label' => 'mautic.pipedrive.push_deal.question',
                        'data'  => (isset($data['push_deal'])) ? (bool) $data['push_deal'] : false,
                        'attr'  => [
                            'tooltip' => 'mautic.pipedrive.push_deal.tooltip',
                        ],
                    ]
                );

                $stages = $this->em->getRepository(PipedriveStage::class)
                    ->createQueryBuilder('st')
                    ->join('st.pipeline', 'p')
                    ->addOrderBy('p.name', 'ASC')
                    ->addOrderBy('st.order', 'ASC')
                    ->getQuery()
                    ->getResult();
                $products = $this->em->getRepository(PipedriveProduct::class)->findBy([], ['name' => 'ASC']);

                $stageChoices = [];
                foreach ($stages as $stage) {
                    $stageChoices[$stage->getPipeline()->getName()][$stage->getId()] =  $stage->getName();
                }

                $productChoices = [];
                foreach ($products as $product) {
                    $productChoices[$product->getId()] = $product->getName();
                }

                $builder->add(
                    'title',
                    'text',
                    [
                        'label' => 'mautic.pipedrive.offer_name.label',
                        'attr'  => [
                            'class' => 'form-control',
                            //'data-show-on' => $pushDeal,
                        ],
                        'required' => true,
                    ]
                );
                $builder->add('stage', 'choice', [
                    'label'   => 'mautic.pipedrive.stage.label',
                    'choices' => $stageChoices,
                    'attr' => [
                        //'data-show-on' => $pushDeal,
                    ],
                ]);

                $builder->add('product', 'choice', [
                    'label'   => 'mautic.pipedrive.product.label',
                    'choices' => $productChoices,
                    'placeholder' => 'mautic.pipedrive.product.placeholder',
                    'attr' => [
                        'tooltip' => 'mautic.pipedrive.product.tooltip',
                        'data-show-on' => $pushDeal,
                    ],
                ]);

                $builder->add(
                    'product_price',
                    'number',
                    [
                        'label' => 'mautic.pipedrive.offer_product_price',
                        'attr'  => [
                            'class' => 'form-control',
                            // 'data-hide-on' => $noProductChosen,
                            // 'data-show-on' => $pushDeal,
                        ],
                        'data'  => (isset($data['product_price']))? $data['product_price'] : 0,
                        'required' => false,
                    ]
                );
                $builder->add(
                    'product_comment',
                    'textarea',
                    [
                        'label' => 'mautic.pipedrive.offer_product_comment',
                        'attr'  => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.pipedrive.product_comment.tooltip',
                            // 'data-hide-on' => $noProductChosen,
                            // 'data-show-on' => $pushDeal,
                        ],
                        'required' => false,
                    ]
                );
            }
        }
    }

    public function isCompanySupportEnabled()
    {
        $supportedFeatures = $this->getIntegrationSettings()->getFeatureSettings();

        return isset($supportedFeatures['objects']) && in_array('company', $supportedFeatures['objects']);
    }

    public function isDealSupportEnabled()
    {
        $supportedFeatures = $this->getIntegrationSettings()->getFeatureSettings();

        return isset($supportedFeatures['objects']) && in_array('deal', $supportedFeatures['objects']);
    }

    public function pushLead($lead, $config = [])
    {
        $leadExport = $this->factory->get('mautic_integration.pipedrive.export.lead');
        $leadExport->setIntegration($this);

        if ($this->isDealSupportEnabled()) {
            $dealExport = $this->factory->get('mautic_integration.pipedrive.export.deal');
            $dealExport->setIntegration($this);

            return $leadExport->createWithDeal($lead, $config['config'], $dealExport);
        } else {
            return $leadExport->create($lead);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        $router     = $this->factory->get('router');
        $translator = $this->getTranslator();

        if ($section == 'authorization') {
            return [$translator->trans('mautic.pipedrive.webhook_callback').$router->generate('mautic_integration.pipedrive.webhook', [], UrlGeneratorInterface::ABSOLUTE_URL), 'info'];
        }

        return parent::getFormNotes($section);
    }
}
