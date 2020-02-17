<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;
use MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport;
use MauticPlugin\MauticCrmBundle\Services\Transport;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class PipedriveIntegration extends CrmAbstractIntegration
{
    const INTEGRATION_NAME         = 'Pipedrive';
    const PERSON_ENTITY_TYPE       = 'person';
    const LEAD_ENTITY_TYPE         = 'lead';
    const ORGANIZATION_ENTITY_TYPE = 'organization';
    const COMPANY_ENTITY_TYPE      = 'company';

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var LeadExport
     */
    private $leadExport;

    /**
     * @var CrmApi
     */
    private $apiHelper;

    private $requiredFields = [
        'person'        => ['firstname', 'lastname', 'email'],
        'organization'  => ['name'],
    ];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CacheStorageHelper $cacheStorageHelper,
        EntityManager $entityManager,
        Session $session,
        RequestStack $requestStack,
        Router $router,
        TranslatorInterface $translator,
        Logger $logger,
        EncryptionHelper $encryptionHelper,
        LeadModel $leadModel,
        CompanyModel $companyModel,
        PathsHelper $pathsHelper,
        NotificationModel $notificationModel,
        FieldModel $fieldModel,
        IntegrationEntityModel $integrationEntityModel,
        DoNotContact $doNotContact,
        Transport $transport,
        LeadExport $leadExport
    ) {
        parent::__construct(
            $eventDispatcher,
            $cacheStorageHelper,
            $entityManager,
            $session,
            $requestStack,
            $router,
            $translator,
            $logger,
            $encryptionHelper,
            $leadModel,
            $companyModel,
            $pathsHelper,
            $notificationModel,
            $fieldModel,
            $integrationEntityModel,
            $doNotContact
        );

        $this->transport  = $transport;
        $this->leadExport = $leadExport;
    }

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
        if (isset($this->getKeys()['url'])) {
            return $this->getKeys()['url'];
        }
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
        return $this->getAvailableLeadFields(self::ORGANIZATION_ENTITY_TYPE);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        $fields = $this->getAvailableLeadFields(self::PERSON_ENTITY_TYPE);

        if (empty($fields)) {
            return [];
        }

        if (isset($fields['org_id'])) {
            unset($fields['org_id']);
        }

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
            $class           = '\\MauticPlugin\\MauticCrmBundle\\Api\\'.$this->getName().'Api'; //TODO replace with service
            $this->apiHelper = new $class($this, $this->transport);
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
        if ('features' == $formArea) {
            $builder->add(
                'objects',
                ChoiceType::class,
                [
                    'choices'     => [
                        'mautic.pipedrive.object.organization'  => 'company',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.pipedrive.form.objects_to_pull_from',
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );

            $builder->add(
                'import',
                ChoiceType::class,
                [
                    'choices'     => [
                        'mautic.pipedrive.add.edit.contact.import.enabled' => 'enabled',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.pipedrive.add.edit.contact.import',
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );
        }
    }

    /**
     * @param array|\Mautic\LeadBundle\Entity\Lead $lead
     * @param array                                $config
     *
     * @return mixed
     */
    public function pushLead($lead, $config = [])
    {
        $this->leadExport->setIntegration($this);

        return $this->leadExport->create($lead);
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
        $router     = $this->router;
        $translator = $this->getTranslator();

        if ('authorization' == $section) {
            return [
                $translator->trans('mautic.pipedrive.webhook_callback').$router->generate(
                    'mautic_integration.pipedrive.webhook',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'info',
            ];
        }

        return parent::getFormNotes($section);
    }

    /**
     * @return bool
     */
    public function isCompanySupportEnabled()
    {
        $supportedFeatures = $this->getIntegrationSettings()->getFeatureSettings();

        return isset($supportedFeatures['objects']) && in_array('company', $supportedFeatures['objects']);
    }

    /**
     * @return bool
     */
    public function shouldImportDataToPipedrive()
    {
        if (!$this->getIntegrationSettings()->getIsPublished() || empty($this->getIntegrationSettings()->getFeatureSettings()['import'])) {
            return false;
        }

        return true;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function removeIntegrationEntities()
    {
        $qb = $this->em->getConnection()->createQueryBuilder();

        return $qb->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('integration', ':integration')
                )
            )
            ->setParameter('integration', $this->getName())
            ->execute();
    }
}
