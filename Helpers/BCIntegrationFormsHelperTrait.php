<?php
declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace MauticPlugin\MauticIntegrationsBundle\Helpers;

use Mautic\PluginBundle\Event\PluginIntegrationFormBuildEvent;
use Mautic\PluginBundle\Event\PluginIntegrationFormDisplayEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

/**
 * Class BCPluginHelper provides interfacing between requirements for old AsbtractIntegration and new integrations.
 */
trait BCIntegrationFormsHelperTrait
{
    /** @inheritdoc */
    public function getDescription()
    : string
    {
        return '';
    }

    /** @inheritdoc */
    public function getDisplayName()
    : string
    {
        return $this->getName();
    }

    /** @inheritdoc */
    public function getPriority()
    : int
    {
        return 9999;
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getRequiredKeyFields()
    {
        return [];
    }

    /**
     * Get available fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields(array $settings = [])
    {
        if (isset($settings['feature_settings']['objects']['company'])) {
            unset($settings['feature_settings']['objects']['company']);
        }

        return ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];
    }

    /**
     * @param FormBuilder $builder
     * @param array $options
     */
    public function modifyForm($builder, $options)
    {
        $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_BUILD,
            new PluginIntegrationFormBuildEvent($this, $builder, $options)
        );
    }

    /**
     * Get if data priority is enabled in the integration or not default is false.
     *
     * @return string
     */
    public function getDataPriority()
    {
        return false;
    }

    public function getSupportedFeatures()
    {
        return [];
    }

    public function getSupportedFeatureTooltips()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getFormDisplaySettings()
    {
        /** @var PluginIntegrationFormDisplayEvent $event */
        $event = $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_FORM_DISPLAY,
            new PluginIntegrationFormDisplayEvent($this, $this->getFormSettings())
        );

        return $event->getSettings();
    }

    /**
     * Returns settings for the integration form.
     *
     * @return array
     */
    public function getFormSettings()
    {
        $type = $this->getAuthenticationType();
        $enableDataPriority = $this->getDataPriority();
        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $callback = $authorization = true;
                break;
            default:
                $callback = $authorization = false;
                break;
        }

        return [
            'requires_callback'      => $callback,
            'requires_authorization' => $authorization,
            'default_features'       => [],
            'enable_data_priority'   => $enableDataPriority,
        ];
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey(): string
    {
        switch ($this->getAuthenticationType()) {
            case 'oauth1a':
                return 'consumer_secret';
            case 'oauth2':
                return 'client_secret';
            case 'basic':
                return 'password';
            default:
                return '';
        }
    }

    /**
     * Array of keys to mask in the config form.
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [$this->getClientSecretKey()];
    }

    /**
     * Allows appending extra data to the config.
     *
     * @param FormBuilder|Form $builder
     * @param array $data
     * @param string $formArea           Section of form being built keys|features|integration
     *                                   keys can be used to store login/request related settings; keys are encrypted
     *                                   features can be used for configuring share buttons, etc
     *                                   integration is called when adding an integration to events like point triggers,
     *                                   campaigns actions, forms actions, etc
     */
    public function appendToForm(FormBuilder $builder, array $data, string $formArea)
    {
    }

    /**
     * Function used to format unformated fields coming from FieldsTypeTrait
     * (usually used in campaign actions).
     * Called after mapping form is submitted from FieldsTypeTrait
     *
     * @param array $fields
     *
     * @return array
     */
    public function formatMatchedFields(array $fields): array
    {
        return $fields;
    }

    /**
     * Checks if an integration is authorized and/or authorizes the request.
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $type = $this->getAuthenticationType();
        $authTokenKey = $this->getAuthTokenKey();

        switch ($type) {
            case 'oauth1a':
            case 'oauth2':
                $refreshTokenKeys = $this->getRefreshTokenKeys();
                if (!isset($this->keys[$authTokenKey])) {
                    $valid = false;
                }
                elseif (!empty($refreshTokenKeys)) {
                    list($refreshTokenKey, $expiryKey) = $refreshTokenKeys;
                    if (!empty($this->keys[$refreshTokenKey]) && !empty($expiryKey) && isset($this->keys[$expiryKey])
                        && time() > $this->keys[$expiryKey]
                    ) {
                        //token has expired so try to refresh it
                        $error = $this->authCallback(['refresh_token' => $refreshTokenKey]);
                        $valid = (empty($error));
                    }
                    else {
                        // The refresh token doesn't have an expiry so the integration will have to check for expired sessions and request new token
                        $valid = true;
                    }
                }
                else {
                    $valid = true;
                }
                break;
            case 'key':
                $valid = isset($this->keys['api_key']);
                break;
            case 'rest':
                $valid = isset($this->keys[$authTokenKey]);
                break;
            case 'basic':
                $valid = (!empty($this->keys['username']) && !empty($this->keys['password']));
                break;
            default:
                $valid = true;
                break;
        }

        return $valid;
    }

    /**
     * Checks to see if the integration is configured by checking that required keys are populated.
     *
     * @return bool
     */
    public function isConfigured()
    {
        $requiredTokens = $this->getRequiredKeyFields();
        foreach ($requiredTokens as $token => $label) {
            if (empty($this->keys[$token])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns notes specific to sections of the integration form (if applicable).
     *
     * @param $section
     *
     * @return array
     */
    public function getFormNotes($section)
    {
        if ($section == 'leadfield_match') {
            return ['mautic.integration.form.field_match_notes', 'info'];
        }
        else {
            return ['', 'info'];
        }
    }

    /**
     * Allows integration to set a custom form template.
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'MauticPluginBundle:Integration:form.html.php';
    }

    /**
     * Allows integration to set a custom theme folder.
     *
     * @return string
     */
    public function getFormTheme()
    {
        return 'MauticPluginBundle:FormTheme\Integration';
    }
}
