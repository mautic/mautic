<?php


/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use GuzzleHttp\Client;
use Spinen\ConnectWise\Api\Client as ConnectWiseClient;
use Spinen\ConnectWise\Api\Token;
use Spinen\ConnectWise\Models\Company\Company;
use Spinen\ConnectWise\Models\Company\Contact;
use Spinen\ConnectWise\Support\ModelResolver;

/**
 * Class ConnectwiseIntegration.
 */
class ConnectwiseIntegration extends CrmAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Connectwise';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'pull_lead'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'companyid' => 'mautic.connectwise.form.companyid',
            'memberid'  => 'mautic.connectwise.form.memberid',
            'username'  => 'mautic.connectwise.form.integrator',
            'password'  => 'mautic.connectwise.form.privatekey',
            'site'      => 'mautic.connectwise.form.site',
            'appcookie' => 'mautic.connectwise.form.cookie',
        ];
    }
    /**
     * Get the array key for application cookie.
     *
     * @return string
     */
    public function getCompanyCookieKey()
    {
        return 'appcookie';
    }

    /**
     * Get the array key for companyid.
     *
     * @return string
     */
    public function getCompanyIdKey()
    {
        return 'companyid';
    }

    /**
     * Get the array key for member id.
     *
     * @return string
     */
    public function getMemberIdKey()
    {
        return 'memberid';
    }

    /**
     * Get the array key for client id.
     *
     * @return string
     */
    public function getIntegrator()
    {
        return 'username';
    }

    /**
     * Get the array key for client id.
     *
     * @return string
     */
    public function getConnectwiseUrl()
    {
        return 'site';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'password',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthLoginUrl()
    {
        return $this->router->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
    }
    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => true,
        ];
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://staging.connectwisedev.com/v4_6_release';
    }

    /**
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $token    = new Token();
        $guzzle   = new Client();
        $resolver = new ModelResolver();
        $client   = new ConnectWiseClient($token, $guzzle, $resolver);
        $headers  = $client->getHeaders();

        $token
            ->setCompanyId($this->keys[$this->getCompanyIdKey()])
            ->setMemberId($this->keys[$this->getMemberIdKey()]);

        $client
            ->setIntegrator($this->keys[$this->getIntegrator()])
            ->setPassword($this->keys[$this->getClientSecretKey()])
            ->setUrl($this->keys[$this->getConnectwiseUrl()]);

        return $this->extractAuthKeys($client);
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
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields()) {
            return $fields;
        }
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
                        'contacts' => 'mautic.connectwise.object.contact',
                        'company'  => 'mautic.connectwise.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.connectwise.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }
}
