<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;

abstract class SocialIntegration extends AbstractIntegration
{
    protected $persistNewLead = false;

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $name = strtolower($this->getName());
            if ($this->factory->serviceExists('mautic.form.type.social.'.$name)) {
                $builder->add('shareButton', 'socialmedia_'.$name, [
                    'label'    => 'mautic.integration.form.sharebutton',
                    'required' => false,
                    'data'     => (isset($data['shareButton'])) ? $data['shareButton'] : [],
                ]);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        static $fields = [];

        if (empty($fields)) {
            $translator = $this->factory->getTranslator();
            $s          = $this->getName();
            $available  = $this->getAvailableLeadFields($settings);
            if (empty($available) || !is_array($available)) {
                return [];
            }
            //create social profile fields
            $socialProfileUrls = $this->factory->getHelper('integration')->getSocialProfileUrlRegex();

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label)
                            ? $translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                            : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                    : $label;
                            }
                        } else {
                            $fields[$field] = (!$label)
                                ? $translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                : $label;
                        }
                        break;
                    case 'array_object':
                        if ($field == 'urls' || $field == 'url') {
                            foreach ($socialProfileUrls as $p => $d) {
                                $fields["{$p}ProfileHandle"] = (!$label)
                                    ? $translator->transConditional("mautic.integration.common.{$p}ProfileHandle", "mautic.integration.{$s}.{$p}ProfileHandle")
                                    : $label;
                            }
                            foreach ($details['fields'] as $f) {
                                $fields["{$p}Urls"] = (!$label)
                                    ? $translator->transConditional("mautic.integration.common.{$f}Urls", "mautic.integration.{$s}.{$f}Urls")
                                    : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label)
                                    ? $translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
                                    : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label)
                                ? $translator->transConditional("mautic.integration.common.{$fn}", "mautic.integration.{$s}.{$fn}")
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

    /**
     * @param array $settings
     */
    public function getFormCompanyFields($settings = [])
    {
        $settings['feature_settings']['objects'] = ['Company'];
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     *
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
     * @param $section
     *
     * @return string
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
        return "MauticSocialBundle:Integration/{$this->getName()}/Profile:view.html.php";
    }

    /**
     * Get the access token from session or socialCache.
     *
     * @param $socialCache
     *
     * @return array|mixed|null
     */
    protected function getContactAccessToken(&$socialCache)
    {
        $session = $this->factory->getSession();
        if (!$session->isStarted()) {
            return (isset($socialCache['accessToken'])) ? $this->decryptApiKeys($socialCache['accessToken']) : null;
        }

        $accessToken = $this->factory->getSession()->get($this->getName().'_tokenResponse', []);
        if (!isset($accessToken[$this->getAuthTokenKey()])) {
            if (isset($socialCache['accessToken'])) {
                $accessToken = $this->decryptApiKeys($socialCache['accessToken']);
            } else {
                return null;
            }
        } else {
            $this->factory->getSession()->remove($this->getName().'_tokenResponse');
            $socialCache['accessToken'] = $this->encryptApiKeys($accessToken);

            $this->persistNewLead = true;
        }

        return $accessToken;
    }
}
