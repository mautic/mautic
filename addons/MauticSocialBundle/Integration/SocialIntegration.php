<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticSocialBundle\Integration;


use Mautic\AddonBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\FormBuilder;

abstract class SocialIntegration extends AbstractIntegration
{

    /**
     * @param FormBuilder $builder
     */
    public function appendToForm(FormBuilder &$builder, $formArea)
    {
        if ($formArea == 'features') {
            $name = strtolower($this->getName());
            if ($this->factory->serviceExists('mautic.form.type.social.' . $name)) {
                $builder->add('shareButton', 'socialmedia_' . $name, array(
                    'label'    => 'mautic.integration.form.sharebutton',
                    'required' => false
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $silenceExceptions
     *
     * @return array
     */
    public function getFormLeadFields($silenceExceptions = true)
    {
        static $fields = array();

        if (empty($fields)) {
            $translator = $this->factory->getTranslator();
            $s          = $this->getName();
            $available  = $this->getAvailableFields($silenceExceptions);
            if (empty($available) || !is_array($available)) {
                return array();
            }
            //create social profile fields
            $socialProfileUrls = $this->factory->getHelper('integration')->getSocialProfileUrlRegex();

            foreach ($available as $field => $details) {
                $label = (!empty($details['label'])) ? $details['label'] : false;
                $fn    = $this->matchFieldName($field);
                switch ($details['type']) {
                    case 'string':
                    case 'boolean':
                        $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        break;
                    case 'object':
                        if (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                            }
                        } else {
                            $fields[$field] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        }
                        break;
                    case 'array_object':
                        if ($field == "urls" || $field == "url") {
                            foreach ($socialProfileUrls as $p => $d) {
                                $fields["{$p}ProfileHandle"] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$p}ProfileHandle") : $label;
                            }
                            foreach ($details['fields'] as $f) {
                                $fields["{$f}Urls"] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$f}Urls") : $label;
                            }
                        } elseif (isset($details['fields'])) {
                            foreach ($details['fields'] as $f) {
                                $fn          = $this->matchFieldName($field, $f);
                                $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                            }
                        } else {
                            $fields[$fn] = (!$label) ? $translator->trans("mautic.integration.{$s}.{$fn}") : $label;
                        }
                        break;
                }
            }
            if ($this->sortFieldsAlphabetically()) {
                uasort($fields, "strnatcmp");
            }
        }

        return $fields;
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
        return array(
            'client_id'      => 'mautic.integration.keyfield.clientid',
            'client_secret'  => 'mautic.integration.keyfield.clientsecret'
        );
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey ()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret
     *
     * @return string
     */
    public function getClientSecretKey ()
    {
        return 'client_secret';
    }

    /**
     * Get the array key for the auth token
     *
     * @return string
     */
    public function getAuthTokenKey ()
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse ($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            return json_decode($data, true);
        } else {
            return json_decode($data);
        }
    }
}