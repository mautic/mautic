<?php

namespace MauticPlugin\MauticSocialBundle\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class SocialIntegration extends AbstractIntegration
{
    protected $persistNewLead = false;

    /**
     * @var Translator
     */
    protected TranslatorInterface $translator;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CacheStorageHelper $cacheStorageHelper,
        EntityManager $entityManager,
        Session $session,
        RequestStack $requestStack,
        Router $router,
        Translator $translator,
        Logger $logger,
        EncryptionHelper $encryptionHelper,
        LeadModel $leadModel,
        CompanyModel $companyModel,
        PathsHelper $pathsHelper,
        NotificationModel $notificationModel,
        FieldModel $fieldModel,
        FieldsWithUniqueIdentifier $fieldsWithUniqueIdentifier,
        IntegrationEntityModel $integrationEntityModel,
        DoNotContact $doNotContact,
        protected IntegrationHelper $integrationHelper
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
        $this->setFieldsWithUniqueIdentifier($fieldsWithUniqueIdentifier);
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' == $formArea) {
            $name     = strtolower($this->getName());
            $formType = $this->getFormType();
            if ($formType) {
                $builder->add('shareButton', $formType, [
                    'label'    => 'mautic.integration.form.sharebutton',
                    'required' => false,
                    'data'     => $data['shareButton'] ?? [],
                ]);
            }
        }
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        static $fields = [];

        if (empty($fields)) {
            $s         = $this->getName();
            $available = $this->getAvailableLeadFields($settings);
            if (empty($available) || !is_array($available)) {
                return [];
            }
            // create social profile fields
            $socialProfileUrls = $this->integrationHelper->getSocialProfileUrlRegex();

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label)
                            ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ('urls' == $field || 'url' == $field) {
                            foreach ($socialProfileUrls as $p => $d) {
                                $fields["{$p}ProfileHandle"] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$p}ProfileHandle", "mautic.integration.{$s}.{$p}ProfileHandle")
                                    : $label;
                            }
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$f}Urls", "mautic.integration.{$s}.{$f}Urls")
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $this->translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                : $label;
                        }
                        break;
                }
            }
            if ($this->sortFieldsAlphabetically()) {
                uasort($fields, 'strnatcmp');
            }
        }

        return $fields;
    }

    public function getFormCompanyFields($settings = [])
    {
        $settings['feature_settings']['objects'] = ['Company'];

        return [];
    }

    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.clientid',
            'client_secret' => 'mautic.integration.keyfield.clientsecret',
        ];
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            return json_decode($data, true);
        } else {
            return json_decode($data);
        }
    }

    /**
     * Returns notes specific to sections of the integration form (if applicable).
     *
     * @return array<mixed>
     */
    public function getFormNotes($section)
    {
        return ['', 'info'];
    }

    /**
     * Get the template for social profiles.
     *
     * @return string
     */
    public function getSocialProfileTemplate()
    {
        return "MauticSocialBundle:Integration/{$this->getName()}/Profile:view.html.twig";
    }

    /**
     * Get the access token from session or socialCache.
     *
     * @return array|mixed|null
     */
    protected function getContactAccessToken(&$socialCache)
    {
        if (!$this->session) {
            return null;
        }

        if (!$this->session->isStarted()) {
            return (isset($socialCache['accessToken'])) ? $this->decryptApiKeys($socialCache['accessToken']) : null;
        }

        $accessToken = $this->session->get($this->getName().'_tokenResponse', []);
        if (!isset($accessToken[$this->getAuthTokenKey()])) {
            if (isset($socialCache['accessToken'])) {
                $accessToken = $this->decryptApiKeys($socialCache['accessToken']);
            } else {
                return null;
            }
        } else {
            $this->session->remove($this->getName().'_tokenResponse');
            $socialCache['accessToken'] = $this->encryptApiKeys($accessToken);

            $this->persistNewLead = true;
        }

        return $accessToken;
    }

    /**
     * Returns form type.
     *
     * @return string|null
     */
    abstract public function getFormType();
}
